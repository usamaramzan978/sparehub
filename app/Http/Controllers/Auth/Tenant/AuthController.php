<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth\Tenant;

use App\Actions\Auth\Tenant\ForgotPasswordAction;
use App\Actions\Auth\Tenant\IssueTwoStepCodeAction;
use App\Actions\Auth\Tenant\LoginAction;
use App\Actions\Auth\Tenant\LogoutAction;
use App\Actions\Auth\Tenant\RegisterAction;
use App\Actions\Auth\Tenant\ResetPasswordAction;
use App\Actions\Auth\Tenant\VerifyTwoStepCodeAction;
use App\Enums\LoginUserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\Tenant\ForgotPasswordRequest;
use App\Http\Requests\Auth\Tenant\LoginRequest;
use App\Http\Requests\Auth\Tenant\RegisterRequest;
use App\Http\Requests\Auth\Tenant\ResetPasswordRequest;
use App\Http\Requests\Auth\Tenant\TwoStepVerificationRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Stancl\Tenancy\Facades\Tenancy;

final class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.tenant.login');
    }

    public function showRegister(): View
    {
        return view('auth.tenant.register');
    }

    public function showForgotPassword(): View
    {
        return view('auth.tenant.forgot-password');
    }

    public function showResetPassword(): View
    {
        return view('auth.tenant.reset-password');
    }

    public function showTwoStep(): View
    {
        return view('auth.tenant.two-step-verification');
    }

    public function login(LoginRequest $request, LoginAction $action): RedirectResponse
    {
        // 1️⃣ Validate credentials (central DB lookup)
        $data = $action->handle(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
            $request->boolean('remember')
        );

        // 2️⃣ Generate one-time nonce stored in central cache
        $nonce = (string) Str::uuid();
        Cache::store('database')->put(
            'login_nonce:'.$nonce,
            [
                'type_id' => $data['type_id'],
                'type' => $data['type'],
                'remember' => $data['remember'],
            ],
            now()->addSeconds(30)
        );

        // 3️⃣ Build signed URL pointing to tenant authenticate route
        $signedUrl = URL::temporarySignedRoute(
            'tenant.authenticate',
            now()->addSeconds(30),
            [
                'tenant' => $data['tenant'],
                'nonce' => $nonce,
            ]
        );

        // 4️⃣ Redirect into tenant context via signed URL
        return redirect()->to($signedUrl);
    }

    public function authenticateTenant(Request $request): RedirectResponse
    {
        // 1️⃣ Validate signed URL
        abort_unless($request->hasValidSignature(), 403, 'Link expired or invalid.');

        $tenantId = (string) tenant()->getTenantKey();

        // 2️⃣ Validate nonce format
        $nonce = (string) $request->query('nonce');

        abort_unless(Str::isUuid($nonce), 403, 'Invalid request.');

        // 3️⃣ Consume nonce atomically from central cache
        $payload = rescue(
            fn () => Tenancy::central(
                fn () => Cache::store('database')->pull('login_nonce:'.$nonce)
            ),
            null,
            false
        );

        if (! $payload) {
            return to_route('tenant.login', ['tenant' => $tenantId])
                ->withErrors(['email' => 'Login link expired. Please try again.']);
        }

        // 4️⃣ Verify login type
        abort_if(($payload['type'] ?? null) !== LoginUserType::USER->value, 403, 'Invalid login type.');

        // 5️⃣ Load user in tenant context
        $user = User::query()
            ->withoutGlobalScope('session_branch')
            ->find($payload['type_id']);

        if (! $user) {
            return to_route('tenant.login', ['tenant' => $tenantId])
                ->withErrors(['email' => 'Login failed.']);
        }

        // 6️⃣ Log user into tenant session
        Auth::guard('user')->login($user, (bool) $payload['remember']);
        $request->session()->regenerate();

        // 7️⃣ Redirect to dashboard
        return to_route('tenant.dashboard', ['tenant' => $tenantId]);
    }

    public function logout(LogoutAction $action): RedirectResponse
    {
        $action->handle();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return to_route('auth.login');
    }

    public function register(RegisterRequest $request, RegisterAction $action, IssueTwoStepCodeAction $twoStep): RedirectResponse
    {
        $user = $action->handle($request->validated());

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $twoStep->handle($request->session(), $user);

        return redirect()->intended('/');
    }

    public function forgotPassword(ForgotPasswordRequest $request, ForgotPasswordAction $action): RedirectResponse
    {
        $action->handle($request->string('email')->toString());

        return back()->with('status', __('passwords.sent'));
    }

    public function resetPassword(ResetPasswordRequest $request, ResetPasswordAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('auth.login')->with('status', __('passwords.reset'));
    }

    public function verifyTwoStep(TwoStepVerificationRequest $request, VerifyTwoStepCodeAction $action): RedirectResponse
    {
        $action->handle($request->session(), $request->input('code', []));

        return redirect()->intended('/');
    }
}

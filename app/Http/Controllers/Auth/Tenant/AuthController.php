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
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\Tenant\ForgotPasswordRequest;
use App\Http\Requests\Auth\Tenant\LoginRequest;
use App\Http\Requests\Auth\Tenant\RegisterRequest;
use App\Http\Requests\Auth\Tenant\ResetPasswordRequest;
use App\Http\Requests\Auth\Tenant\TwoStepVerificationRequest;
use App\Models\LoginMap;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Stancl\Tenancy\Facades\Tenancy;

final class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        // ✅ If user has tenant in session, redirect them there
        $userTenantId = session('user_tenant_id');

        if ($userTenantId) {
            return redirect()->route('tenant.dashboard', ['tenant' => $userTenantId]);
        }

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
        $data = $action->handle(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
            $request->boolean('remember')
        );

        // ✅ Multiple tenants — show picker
        if (isset($data['multiple_tenants'])) {
            session()->put('multi_tenant_login', [
                'tenants' => $data['tenants'],
                'remember' => $data['remember'],
                'expires_at' => now()->addMinutes(5),
            ]);

            return to_route('auth.choose-tenant');
        }

        // ✅ Single tenant — proceed to authenticate
        return $this->generateAuthenticateRedirect(
            $data['tenant'],
            $data['type_id'],
            $data['type'],
            $data['remember']
        );
    }

    public function showChooseTenant(): View|RedirectResponse
    {
        $data = session('multi_tenant_login');

        if (! $data || now()->gt($data['expires_at'])) {
            return to_route('auth.login')
                ->withErrors(['email' => 'Session expired. Please login again.']);
        }

        return view('auth.tenant.choose-tenant', [
            'tenants' => $data['tenants'],
        ]);
    }

    public function chooseTenant(Request $request): RedirectResponse
    {
        $data = session('multi_tenant_login');

        if (! $data || now()->gt($data['expires_at'])) {
            return to_route('auth.login')
                ->withErrors(['email' => 'Session expired. Please login again.']);
        }

        $selectedTenantId = $request->input('tenant_id');

        // ✅ Verify the selected tenant was in the validated list
        $tenant = collect($data['tenants'])
            ->firstWhere('tenant_id', $selectedTenantId);

        abort_unless($tenant, 403, 'Unauthorized tenant selection.');

        session()->forget('multi_tenant_login');

        return $this->generateAuthenticateRedirect(
            $tenant['tenant_id'],
            $tenant['type_id'],
            $tenant['type'],
            $data['remember']
        );
    }

    public function authenticateTenant(Request $request): RedirectResponse
    {
        abort_unless($request->hasValidSignature(), 403, 'Link expired or invalid.');

        $tenantId = (string) tenant()->getTenantKey();

        $nonce = (string) $request->query('nonce');

        abort_unless(Str::isUuid($nonce), 403, 'Invalid request.');

        $payload = rescue(
            fn() => Tenancy::central(
                fn() => Cache::store('database')->pull('login_nonce:' . $nonce)
            ),
            null,
            false
        );

        if (! $payload) {
            return to_route('tenant.login', ['tenant' => $tenantId])
                ->withErrors(['email' => 'Login link expired. Please try again.']);
        }

        abort_if(($payload['type'] ?? null) !== LoginUserType::USER->value, 403, 'Invalid login type.');
        abort_if(($payload['tenant_id'] ?? null) !== $tenantId, 403, 'Invalid tenant context.');

        $activeLoginMapExists = LoginMap::query()
            ->where('tenant_id', $tenantId)
            ->where('type', LoginUserType::USER->value)
            ->where('type_id', (string) $payload['type_id'])
            ->where('status', true)
            ->exists();

        if (! $activeLoginMapExists) {
            return to_route('tenant.login', ['tenant' => $tenantId])
                ->withErrors(['email' => 'Your account is inactive. Please contact administrator.']);
        }

        $user = User::query()
            ->withoutGlobalScope('session_branch')
            ->find($payload['type_id']);

        if (! $user) {
            return to_route('tenant.login', ['tenant' => $tenantId])
                ->withErrors(['email' => 'Login failed.']);
        }

        $userStatus = $user->status instanceof UserStatus ? $user->status->value : (string) $user->status;
        if ($userStatus !== UserStatus::ACTIVE->value) {
            return to_route('tenant.login', ['tenant' => $tenantId])
                ->withErrors(['email' => 'Your account is inactive. Please contact administrator.']);
        }

        Auth::guard('user')->login($user, (bool) $payload['remember']);
        $request->session()->regenerate();

        // ✅ Store tenant in session for later redirect detection
        session(['user_tenant_id' => $tenantId]);

        return to_route('tenant.dashboard', ['tenant' => $tenantId]);
    }

    public function logout(LogoutAction $action): RedirectResponse
    {
        $action->handle();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        // ✅ Clear tenant ID from session
        session()->forget('user_tenant_id');

        return to_route('auth.login');
        // return redirect()->route('tenant.login', [
        //     'tenant' => tenant()->getTenantKey()
        // ]);
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

    private function generateAuthenticateRedirect(
        string $tenantId,
        string $typeId,
        string $type,
        bool $remember
    ): RedirectResponse {
        $nonce = (string) Str::uuid();

        Cache::store('database')->put(
            'login_nonce:' . $nonce,
            [
                'type_id' => $typeId,
                'type' => $type,
                'remember' => $remember,
                'tenant_id' => $tenantId,
            ],
            now()->addSeconds(30)
        );

        $signedUrl = URL::temporarySignedRoute(
            'tenant.authenticate',
            now()->addSeconds(30),
            [
                'tenant' => $tenantId,
                'nonce' => $nonce,
            ]
        );

        return redirect()->to($signedUrl);
    }
}

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
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;

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
        $data = $action->handle(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
            $request->boolean('remember')
        );

        return to_route('tenant.authenticate', [
            'tenant' => $data['tenant'],
            'token' => $data['token'],
        ]);
    }

    public function authenticateTenant(Request $request): RedirectResponse
    {
        $loginInfo = json_decode((string) Crypt::decrypt($request->token), true);

        $tenantId = tenant('id');

        if (! isset($loginInfo['type'])) {
            return redirect('/')->withErrors(['email' => 'Invalid token']);
        }

        if ($loginInfo['type'] === LoginUserType::USER->value) {
            $request->session()->forget('tenant.current_branch_id');

            $rememberUser = (bool) ($loginInfo['remember'] ?? false);
            $mappedUserId = $loginInfo['type_id'] ?? null;
            $email = mb_strtolower((string) ($loginInfo['email'] ?? ''));

            $tenantUser = null;

            if (is_scalar($mappedUserId) && (string) $mappedUserId !== '') {
                $tenantUser = User::query()
                    ->withoutGlobalScope('session_branch')
                    ->find((string) $mappedUserId);
            }

            if ($tenantUser === null && $email !== '') {
                $tenantUser = User::query()
                    ->withoutGlobalScope('session_branch')
                    ->where('email', $email)
                    ->first();
            }

            if ($tenantUser === null) {
                return to_route('tenant.login', ['tenant' => $tenantId])
                    ->withErrors(['email' => trans('auth.failed')]);
            }

            Auth::guard('user')->login($tenantUser, $rememberUser);
            $request->session()->regenerate();

            return to_route('tenant.dashboard', [
                'tenant' => $tenantId,
            ]);
        }

        return to_route('tenant.login', ['tenant' => $tenantId])
            ->withErrors(['email' => trans('auth.failed')]);
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

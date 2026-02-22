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
use App\Enums\TwoFactorMethod;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\Tenant\ForgotPasswordRequest;
use App\Http\Requests\Auth\Tenant\LoginRequest;
use App\Http\Requests\Auth\Tenant\RegisterRequest;
use App\Http\Requests\Auth\Tenant\ResetPasswordRequest;
use App\Http\Requests\Auth\Tenant\TwoStepVerificationRequest;
use App\Models\LoginMap;
use App\Models\TenantSetting;
use App\Models\User;
use App\Support\AuditTimelineLogger;
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
        // $userTenantId = session('user_tenant_id');

        // if ($userTenantId) {
        //     return redirect()->route('tenant.dashboard', ['tenant' => $userTenantId]);
        // }

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
        $method = TwoFactorMethod::tryFrom((string) session('two_step.method')) ?? TwoFactorMethod::EMAIL;

        return view('auth.tenant.two-step-verification', [
            'method' => $method,
        ]);
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

    public function authenticateTenant(Request $request, IssueTwoStepCodeAction $twoStep): RedirectResponse
    {
        abort_unless($request->hasValidSignature(), 403, 'Link expired or invalid.');

        $tenantId = (string) tenant()->getTenantKey();

        $nonce = (string) $request->query('nonce');

        abort_unless(Str::isUuid($nonce), 403, 'Invalid request.');

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

        $settings = TenantSetting::query()
            ->where('branch_id', $user->branch_id)
            ->first();

        if (! $settings?->two_factor_enabled) {
            $request->session()->put('two_step.required', false);
            $request->session()->put('two_step.verified', true);
            $request->session()->forget(['two_step.method', 'two_step.setup_required', 'two_step.enrollment_required', 'two_step.code', 'two_step.expires_at']);

            AuditTimelineLogger::log(
                event: 'auth_login_succeeded',
                description: 'Tenant login succeeded.',
                causer: $user,
                subject: $user,
                properties: [
                    'two_factor_required' => false,
                ],
            );

            return to_route('tenant.dashboard', ['tenant' => $tenantId]);
        }

        $method = $settings->two_factor_method;

        if ($method === TwoFactorMethod::AUTHENTICATOR) {
            $setupRequired = $user->two_factor_secret === null || $user->two_factor_verified_at === null;

            $request->session()->put('two_step.required', true);
            $request->session()->put('two_step.verified', false);
            $request->session()->put('two_step.method', TwoFactorMethod::AUTHENTICATOR->value);
            $request->session()->put('two_step.setup_required', $setupRequired);
            $request->session()->put('two_step.enrollment_required', $setupRequired);
            $request->session()->forget(['two_step.code', 'two_step.expires_at']);

            if ($setupRequired) {
                AuditTimelineLogger::log(
                    event: 'two_factor_enrollment_required',
                    description: 'Authenticator enrollment required before completing login.',
                    causer: $user,
                    subject: $user,
                    properties: [
                        'method' => TwoFactorMethod::AUTHENTICATOR->value,
                    ],
                );

                return to_route('tenant.profile.security.show', ['tenant' => $tenantId])
                    ->with('status', 'Complete authenticator enrollment from Security before continuing.');
            }

            AuditTimelineLogger::log(
                event: 'two_step_challenge_required',
                description: 'Two-step verification challenge required.',
                causer: $user,
                subject: $user,
                properties: [
                    'method' => TwoFactorMethod::AUTHENTICATOR->value,
                ],
            );

            return to_route('tenant.two-step', ['tenant' => $tenantId]);
        }

        $twoStep->handle($request->session(), $user);

        return to_route('tenant.two-step', ['tenant' => $tenantId]);
    }

    public function logout(LogoutAction $action): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::guard('user')->user();
        if ($user) {
            AuditTimelineLogger::log(
                event: 'auth_logout',
                description: 'Tenant user logged out.',
                causer: $user,
                subject: $user,
            );
        }

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
        /** @var User $user */
        $user = $request->user('user');
        $method = TwoFactorMethod::tryFrom((string) $request->session()->get('two_step.method')) ?? TwoFactorMethod::EMAIL;
        $usedBackupCode = mb_trim($request->string('backup_code')->toString()) !== '';

        $action->handle(
            $request->session(),
            is_array($request->input('code')) ? $request->input('code', []) : [],
            $user,
            $request->string('backup_code')->toString()
        );

        AuditTimelineLogger::log(
            event: 'auth_login_succeeded',
            description: 'Tenant login succeeded after two-step verification.',
            causer: $user,
            subject: $user,
            properties: [
                'two_factor_required' => true,
                'method' => $method->value,
                'used_backup_code' => $usedBackupCode,
            ],
        );

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
            'login_nonce:'.$nonce,
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

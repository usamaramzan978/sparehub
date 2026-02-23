<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\TwoFactorMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UpdateProfileRequest;
use App\Http\Requests\Tenant\VerifyAuthenticatorEnrollmentRequest;
use App\Models\LoginAttempt;
use App\Models\TenantSetting;
use App\Models\User;
use App\Support\AuditTimelineLogger;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PragmaRX\Google2FA\Google2FA;

final class ProfileController extends Controller
{
    public function show(): View
    {
        $user = Auth::guard('user')->user();
        abort_if($user === null, 403);

        $user->load('branch');

        $openedSessions = 0;
        if (Schema::connection('tenant')->hasTable('login_attempts')) {
            $openedSessions = LoginAttempt::query()
                ->where('user_id', $user->id)
                ->where('status', 'success')
                ->count();
        }

        $stats = [
            'opened_sessions' => $openedSessions,
            'closed_sessions' => 0,
            'price_changes' => 0,
        ];

        return view('tenants.profile.show', [
            'user' => $user,
            'stats' => $stats,
        ]);
    }

    public function edit(): View
    {
        $user = Auth::guard('user')->user();
        abort_if($user === null, 403);

        return view('tenants.profile.edit', ['user' => $user]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = Auth::guard('user')->user();
        abort_if($user === null, 403);

        $data = $request->validated();
        $data['email'] = mb_strtolower((string) $data['email']);

        if (empty($data['password'])) {
            $data = Arr::except($data, ['password']);
        } else {
            $data['password'] = Hash::make((string) $data['password']);
        }

        $user->update($data);

        AuditTimelineLogger::log(
            event: 'profile_updated',
            description: 'Profile updated.',
            causer: $user,
            subject: $user,
            properties: [
                'changed_attributes' => array_keys($data),
            ],
        );

        return to_route('tenant.profile.show')
            ->with('status', 'Profile updated.');
    }

    public function security(): View
    {
        /** @var User|null $user */
        $user = Auth::guard('user')->user()?->fresh();
        abort_if($user === null, 403);

        $settings = TenantSetting::query()
            ->withoutGlobalScope('session_branch')
            ->where('branch_id', $this->currentBranchId())
            ->first();

        return view('tenants.profile.security', [
            'user' => $user,
            'authenticatorEnabledForTenant' => $settings?->two_factor_enabled && $settings->two_factor_method === TwoFactorMethod::AUTHENTICATOR,
            'twoFactorMethod' => $settings?->two_factor_method,
            'setupData' => $this->buildAuthenticatorSetupData($user),
            'recentBackupCodes' => session('security.backup_codes'),
        ]);
    }

    public function setupAuthenticator(): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::guard('user')->user()?->fresh();
        abort_if($user === null, 403);

        if (! $this->authenticatorEnabledForTenant()) {
            return to_route('tenant.profile.security.show')
                ->withErrors(['security' => 'Authenticator method is not enabled in tenant settings.']);
        }

        [$secret, $backupCodes, $hashedBackupCodes] = $this->generateAuthenticatorCredentials();

        $user->forceFill([
            'two_factor_type' => 'app',
            'two_factor_secret' => $secret,
            'two_factor_verified_at' => null,
            'two_factor_recovery_codes' => $hashedBackupCodes,
        ])->save();

        AuditTimelineLogger::log(
            event: 'two_factor_enrollment_started',
            description: 'Authenticator enrollment started.',
            causer: $user,
            subject: $user,
            properties: [
                'backup_codes_count' => count($backupCodes),
            ],
        );

        return to_route('tenant.profile.security.show')
            ->with('status', 'Authenticator setup started. Scan the QR code and verify once.')
            ->with('security.backup_codes', $backupCodes);
    }

    public function verifyAuthenticator(VerifyAuthenticatorEnrollmentRequest $request): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::guard('user')->user()?->fresh();
        abort_if($user === null, 403);

        if (! $this->authenticatorEnabledForTenant()) {
            return to_route('tenant.profile.security.show')
                ->withErrors(['security' => 'Authenticator method is not enabled in tenant settings.']);
        }

        $secret = (string) ($user->two_factor_secret ?? '');
        if ($secret === '') {
            return to_route('tenant.profile.security.show')
                ->withErrors(['security' => 'Please start authenticator setup first.']);
        }

        $code = (string) $request->string('code')->toString();
        $google2fa = new Google2FA();
        $isValid = $google2fa->verifyKey($secret, $code, 1);

        if (! $isValid) {
            return to_route('tenant.profile.security.show')
                ->withErrors(['code' => 'Invalid authenticator code.']);
        }

        $user->forceFill([
            'two_factor_type' => 'app',
            'two_factor_verified_at' => now(),
        ])->save();

        AuditTimelineLogger::log(
            event: 'two_factor_enrollment_verified',
            description: 'Authenticator enrollment verified.',
            causer: $user,
            subject: $user,
        );

        if ($request->session()->get('two_step.enrollment_required', false)) {
            $request->session()->put('two_step.required', false);
            $request->session()->put('two_step.verified', true);
            $request->session()->forget(['two_step.method', 'two_step.setup_required', 'two_step.enrollment_required', 'two_step.code', 'two_step.expires_at']);

            return to_route('tenant.dashboard')
                ->with('status', 'Authenticator setup complete.');
        }

        return to_route('tenant.profile.security.show')
            ->with('status', 'Authenticator setup complete.');
    }

    public function resetAuthenticator(): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::guard('user')->user()?->fresh();
        abort_if($user === null, 403);

        if (! $this->authenticatorEnabledForTenant()) {
            return to_route('tenant.profile.security.show')
                ->withErrors(['security' => 'Authenticator method is not enabled in tenant settings.']);
        }

        [$secret, $backupCodes, $hashedBackupCodes] = $this->generateAuthenticatorCredentials();

        $user->forceFill([
            'two_factor_type' => 'app',
            'two_factor_secret' => $secret,
            'two_factor_verified_at' => null,
            'two_factor_recovery_codes' => $hashedBackupCodes,
        ])->save();

        AuditTimelineLogger::log(
            event: 'two_factor_secret_reset',
            description: 'Authenticator secret reset.',
            causer: $user,
            subject: $user,
            properties: [
                'backup_codes_count' => count($backupCodes),
            ],
        );

        return to_route('tenant.profile.security.show')
            ->with('status', 'Authenticator secret has been reset. Verify with a fresh app code.')
            ->with('security.backup_codes', $backupCodes);
    }

    public function regenerateBackupCodes(): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::guard('user')->user()?->fresh();
        abort_if($user === null, 403);

        if (! $this->authenticatorEnabledForTenant()) {
            return to_route('tenant.profile.security.show')
                ->withErrors(['security' => 'Authenticator method is not enabled in tenant settings.']);
        }

        if (! $user->two_factor_secret) {
            return to_route('tenant.profile.security.show')
                ->withErrors(['security' => 'Start authenticator setup before generating backup codes.']);
        }

        [, $backupCodes, $hashedBackupCodes] = $this->generateAuthenticatorCredentials();

        $user->forceFill([
            'two_factor_recovery_codes' => $hashedBackupCodes,
        ])->save();

        AuditTimelineLogger::log(
            event: 'two_factor_backup_codes_regenerated',
            description: 'Authenticator backup codes regenerated.',
            causer: $user,
            subject: $user,
            properties: [
                'backup_codes_count' => count($backupCodes),
            ],
        );

        return to_route('tenant.profile.security.show')
            ->with('status', 'Backup codes regenerated.')
            ->with('security.backup_codes', $backupCodes);
    }

    /**
     * @return array{qr_svg: ?string, secret: ?string, needs_verification: bool, backup_codes_count: int}
     */
    private function buildAuthenticatorSetupData(User $user): array
    {
        if (! $user->two_factor_secret) {
            return [
                'qr_svg' => null,
                'secret' => null,
                'needs_verification' => false,
                'backup_codes_count' => is_array($user->two_factor_recovery_codes) ? count($user->two_factor_recovery_codes) : 0,
            ];
        }

        $google2fa = new Google2FA();
        $issuer = config('app.name', 'SpareHub');
        $qrCodeUrl = $google2fa->getQRCodeUrl($issuer, $user->email, (string) $user->two_factor_secret);
        $renderer = new ImageRenderer(new RendererStyle(220), new SvgImageBackEnd());
        $writer = new Writer($renderer);

        return [
            'qr_svg' => $writer->writeString($qrCodeUrl),
            'secret' => (string) $user->two_factor_secret,
            'needs_verification' => $user->two_factor_verified_at === null,
            'backup_codes_count' => is_array($user->two_factor_recovery_codes) ? count($user->two_factor_recovery_codes) : 0,
        ];
    }

    /**
     * @return array{0: string, 1: array<int, string>, 2: array<int, string>}
     */
    private function generateAuthenticatorCredentials(): array
    {
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();
        $backupCodes = collect(range(1, 8))
            ->map(fn (): string => mb_strtoupper(mb_substr(bin2hex(random_bytes(4)), 0, 4).'-'.mb_substr(bin2hex(random_bytes(4)), 0, 4)))
            ->all();
        $hashedBackupCodes = collect($backupCodes)
            ->map(fn (string $code): string => Hash::make($this->normalizeBackupCode($code)))
            ->all();

        return [$secret, $backupCodes, $hashedBackupCodes];
    }

    private function authenticatorEnabledForTenant(): bool
    {
        $settings = TenantSetting::query()
            ->withoutGlobalScope('session_branch')
            ->where('branch_id', $this->currentBranchId())
            ->first();

        return $settings?->two_factor_enabled && $settings->two_factor_method === TwoFactorMethod::AUTHENTICATOR;
    }

    private function normalizeBackupCode(string $code): string
    {
        return mb_strtoupper(str_replace([' ', '-'], '', mb_trim($code)));
    }
}

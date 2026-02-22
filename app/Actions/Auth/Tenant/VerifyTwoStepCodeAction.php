<?php

declare(strict_types=1);

namespace App\Actions\Auth\Tenant;

use App\Enums\TwoFactorMethod;
use App\Models\User;
use App\Support\AuditTimelineLogger;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

final class VerifyTwoStepCodeAction
{
    /**
     * @param  array<int, string>  $digits
     */
    public function handle(Session $session, array $digits, User $user, ?string $backupCode = null): void
    {
        $method = TwoFactorMethod::tryFrom((string) $session->get('two_step.method')) ?? TwoFactorMethod::EMAIL;
        $provided = implode('', $digits);

        if ($method === TwoFactorMethod::AUTHENTICATOR) {
            if (is_string($backupCode) && mb_trim($backupCode) !== '') {
                $this->verifyBackupCode($session, $backupCode, $user);

                return;
            }

            $this->verifyAuthenticatorCode($session, $provided, $user);

            return;
        }

        $expected = (string) $session->get('two_step.code');
        $expiresAt = $session->get('two_step.expires_at');

        if ($expected === '' || ! $expiresAt) {
            $this->logVerificationFailed($user, $method, 'code_not_available');
            throw ValidationException::withMessages([
                'code' => ['Verification code has expired. Please request a new one.'],
            ]);
        }

        if ($expiresAt instanceof Carbon && $expiresAt->isPast()) {
            $this->clear($session);
            $this->logVerificationFailed($user, $method, 'code_expired');

            throw ValidationException::withMessages([
                'code' => ['Verification code has expired. Please request a new one.'],
            ]);
        }

        if (! hash_equals($expected, $provided)) {
            $this->logVerificationFailed($user, $method, 'invalid_code');
            throw ValidationException::withMessages([
                'code' => ['Invalid verification code.'],
            ]);
        }

        $this->clear($session);
        $session->put('two_step.verified', true);
        $session->put('two_step.required', false);

        AuditTimelineLogger::log(
            event: 'two_step_verified',
            description: 'Two-step verification succeeded.',
            causer: $user,
            subject: $user,
            properties: [
                'method' => $method->value,
            ],
        );
    }

    private function clear(Session $session): void
    {
        $session->forget('two_step.code');
        $session->forget('two_step.expires_at');
        $session->forget('two_step.method');
        $session->forget('two_step.setup_required');
        $session->forget('two_step.enrollment_required');
    }

    private function verifyAuthenticatorCode(Session $session, string $provided, User $user): void
    {
        if (! preg_match('/^\d{6}$/', $provided)) {
            $this->logVerificationFailed($user, TwoFactorMethod::AUTHENTICATOR, 'invalid_format');
            throw ValidationException::withMessages([
                'code' => ['Authenticator code must be 6 digits.'],
            ]);
        }

        $secret = (string) ($user->two_factor_secret ?? '');
        if ($secret === '') {
            $this->logVerificationFailed($user, TwoFactorMethod::AUTHENTICATOR, 'secret_missing');
            throw ValidationException::withMessages([
                'code' => ['Authenticator setup is incomplete. Please scan the QR code and try again.'],
            ]);
        }

        $google2fa = new Google2FA();
        $isValid = $google2fa->verifyKey($secret, $provided, 1);

        if (! $isValid) {
            $this->logVerificationFailed($user, TwoFactorMethod::AUTHENTICATOR, 'invalid_code');
            throw ValidationException::withMessages([
                'code' => ['Invalid verification code.'],
            ]);
        }

        if ($user->two_factor_verified_at === null) {
            $user->forceFill([
                'two_factor_type' => 'app',
                'two_factor_verified_at' => now(),
            ])->save();
        }

        $this->clear($session);
        $session->put('two_step.verified', true);
        $session->put('two_step.required', false);

        AuditTimelineLogger::log(
            event: 'two_step_verified',
            description: 'Two-step verification succeeded.',
            causer: $user,
            subject: $user,
            properties: [
                'method' => TwoFactorMethod::AUTHENTICATOR->value,
            ],
        );
    }

    private function verifyBackupCode(Session $session, string $backupCode, User $user): void
    {
        $normalizedCode = mb_strtoupper(str_replace([' ', '-'], '', mb_trim($backupCode)));

        if ($normalizedCode === '') {
            $this->logVerificationFailed($user, TwoFactorMethod::AUTHENTICATOR, 'backup_code_missing');
            throw ValidationException::withMessages([
                'backup_code' => ['Backup code is required.'],
            ]);
        }

        $recoveryCodes = is_array($user->two_factor_recovery_codes) ? $user->two_factor_recovery_codes : [];
        $matchedIndex = null;

        foreach ($recoveryCodes as $index => $hashedCode) {
            if (is_string($hashedCode) && Hash::check($normalizedCode, $hashedCode)) {
                $matchedIndex = $index;
                break;
            }
        }

        if ($matchedIndex === null) {
            $this->logVerificationFailed($user, TwoFactorMethod::AUTHENTICATOR, 'invalid_backup_code');
            throw ValidationException::withMessages([
                'backup_code' => ['Invalid backup code.'],
            ]);
        }

        unset($recoveryCodes[$matchedIndex]);

        $user->forceFill([
            'two_factor_recovery_codes' => array_values($recoveryCodes),
        ])->save();

        $this->clear($session);
        $session->put('two_step.verified', true);
        $session->put('two_step.required', false);

        AuditTimelineLogger::log(
            event: 'two_step_verified',
            description: 'Two-step verification succeeded with backup code.',
            causer: $user,
            subject: $user,
            properties: [
                'method' => TwoFactorMethod::AUTHENTICATOR->value,
                'used_backup_code' => true,
                'remaining_backup_codes' => count($recoveryCodes),
            ],
        );
    }

    private function logVerificationFailed(User $user, TwoFactorMethod $method, string $reason): void
    {
        AuditTimelineLogger::log(
            event: 'two_step_verification_failed',
            description: 'Two-step verification failed.',
            causer: $user,
            subject: $user,
            properties: [
                'method' => $method->value,
                'reason' => $reason,
            ],
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Auth\Tenant;

use App\Enums\TwoFactorMethod;
use App\Models\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

final class VerifyTwoStepCodeAction
{
    /**
     * @param  array<int, string>  $digits
     */
    public function handle(Session $session, array $digits, User $user): void
    {
        $method = TwoFactorMethod::tryFrom((string) $session->get('two_step.method')) ?? TwoFactorMethod::EMAIL;
        $provided = implode('', $digits);

        if ($method === TwoFactorMethod::AUTHENTICATOR) {
            $this->verifyAuthenticatorCode($session, $provided, $user);

            return;
        }

        $expected = (string) $session->get('two_step.code');
        $expiresAt = $session->get('two_step.expires_at');

        if ($expected === '' || ! $expiresAt) {
            throw ValidationException::withMessages([
                'code' => ['Verification code has expired. Please request a new one.'],
            ]);
        }

        if ($expiresAt instanceof Carbon && $expiresAt->isPast()) {
            $this->clear($session);

            throw ValidationException::withMessages([
                'code' => ['Verification code has expired. Please request a new one.'],
            ]);
        }

        if (! hash_equals($expected, $provided)) {
            throw ValidationException::withMessages([
                'code' => ['Invalid verification code.'],
            ]);
        }

        $this->clear($session);
        $session->put('two_step.verified', true);
        $session->put('two_step.required', false);
    }

    private function clear(Session $session): void
    {
        $session->forget('two_step.code');
        $session->forget('two_step.expires_at');
        $session->forget('two_step.method');
        $session->forget('two_step.setup_required');
    }

    private function verifyAuthenticatorCode(Session $session, string $provided, User $user): void
    {
        if (! preg_match('/^\d{6}$/', $provided)) {
            throw ValidationException::withMessages([
                'code' => ['Authenticator code must be 6 digits.'],
            ]);
        }

        $secret = (string) ($user->two_factor_secret ?? '');
        if ($secret === '') {
            throw ValidationException::withMessages([
                'code' => ['Authenticator setup is incomplete. Please scan the QR code and try again.'],
            ]);
        }

        $google2fa = new Google2FA();
        $isValid = $google2fa->verifyKey($secret, $provided, 1);

        if (! $isValid) {
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
    }
}

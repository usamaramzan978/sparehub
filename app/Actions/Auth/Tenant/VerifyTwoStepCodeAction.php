<?php

declare(strict_types=1);

namespace App\Actions\Auth\Tenant;

use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

final class VerifyTwoStepCodeAction
{
    /**
     * @param  array<int, string>  $digits
     */
    public function handle(Session $session, array $digits): void
    {
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

        $provided = implode('', $digits);

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
    }
}

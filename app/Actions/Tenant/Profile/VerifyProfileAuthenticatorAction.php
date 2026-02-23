<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Profile;

use App\Models\User;
use App\Support\AuditTimelineLogger;
use Illuminate\Http\Request;
use PragmaRX\Google2FA\Google2FA;

final class VerifyProfileAuthenticatorAction
{
    /**
     * @return array{ok: bool, error_key?: string, error_message?: string, redirect_dashboard?: bool}
     */
    public function handle(User $user, string $code, Request $request): array
    {
        $secret = (string) ($user->two_factor_secret ?? '');
        if ($secret === '') {
            return [
                'ok' => false,
                'error_key' => 'security',
                'error_message' => 'Please start authenticator setup first.',
            ];
        }

        $google2fa = new Google2FA();
        $isValid = $google2fa->verifyKey($secret, $code, 1);

        if (! $isValid) {
            return [
                'ok' => false,
                'error_key' => 'code',
                'error_message' => 'Invalid authenticator code.',
            ];
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

            return [
                'ok' => true,
                'redirect_dashboard' => true,
            ];
        }

        return ['ok' => true];
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Profile;

use App\Models\User;
use App\Support\AuditTimelineLogger;

final readonly class ResetProfileAuthenticatorAction
{
    public function __construct(private ProfileAuthenticatorServiceAction $profileAuthenticatorServiceAction) {}

    /**
     * @return array<int, string>
     */
    public function handle(User $user): array
    {
        [$secret, $backupCodes, $hashedBackupCodes] = $this->profileAuthenticatorServiceAction->generateCredentials();

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

        return $backupCodes;
    }
}

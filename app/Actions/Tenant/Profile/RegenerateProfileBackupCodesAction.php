<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Profile;

use App\Models\User;
use App\Support\AuditTimelineLogger;

final class RegenerateProfileBackupCodesAction
{
    public function __construct(private ProfileAuthenticatorServiceAction $profileAuthenticatorServiceAction) {}

    /**
     * @return array<int, string>
     */
    public function handle(User $user): array
    {
        [, $backupCodes, $hashedBackupCodes] = $this->profileAuthenticatorServiceAction->generateCredentials();

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

        return $backupCodes;
    }
}

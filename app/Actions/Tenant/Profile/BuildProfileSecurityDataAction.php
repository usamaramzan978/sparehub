<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Profile;

use App\Enums\TwoFactorMethod;
use App\Models\TenantSetting;
use App\Models\User;

final class BuildProfileSecurityDataAction
{
    public function __construct(private ProfileAuthenticatorServiceAction $profileAuthenticatorServiceAction) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(User $user, string $branchId, mixed $recentBackupCodes): array
    {
        $settings = TenantSetting::query()
            ->withoutGlobalScope('session_branch')
            ->where('branch_id', $branchId)
            ->first();

        return [
            'user' => $user,
            'authenticatorEnabledForTenant' => $settings?->two_factor_enabled && $settings->two_factor_method === TwoFactorMethod::AUTHENTICATOR,
            'twoFactorMethod' => $settings?->two_factor_method,
            'setupData' => $this->profileAuthenticatorServiceAction->buildSetupData($user),
            'recentBackupCodes' => $recentBackupCodes,
        ];
    }
}

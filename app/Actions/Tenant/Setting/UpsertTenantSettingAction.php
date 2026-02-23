<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Setting;

use App\Models\TenantSetting;
use App\Support\AuditTimelineLogger;
use Illuminate\Contracts\Session\Session;

final class UpsertTenantSettingAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data, string $branchId, Session $session, mixed $causer): TenantSetting
    {
        $settings = TenantSetting::query()->firstOrNew(['branch_id' => $branchId]);
        $settings->branch_id = $branchId;

        $previousTwoFactorEnabled = (bool) $settings->two_factor_enabled;
        $previousTwoFactorMethod = $settings->two_factor_method?->value;

        if (! ((bool) ($data['two_factor_enabled'] ?? false))) {
            $data['two_factor_method'] = null;
            $session->put('two_step.required', false);
            $session->put('two_step.verified', true);
            $session->forget([
                'two_step.method',
                'two_step.setup_required',
                'two_step.enrollment_required',
                'two_step.code',
                'two_step.expires_at',
            ]);
        }

        unset($data['logo']);

        $settings->fill($data);
        $settings->save();

        $changedAttributes = array_keys($settings->getChanges());

        AuditTimelineLogger::log(
            event: 'settings_updated',
            description: 'Tenant settings updated.',
            causer: $causer,
            subject: $settings,
            properties: [
                'changed_attributes' => $changedAttributes,
                'branch_id' => $branchId,
            ],
        );

        $currentTwoFactorMethod = $settings->two_factor_method?->value;
        if (
            $previousTwoFactorEnabled !== (bool) $settings->two_factor_enabled
            || $previousTwoFactorMethod !== $currentTwoFactorMethod
        ) {
            AuditTimelineLogger::log(
                event: 'two_factor_policy_changed',
                description: 'Two-factor policy updated.',
                causer: $causer,
                subject: $settings,
                properties: [
                    'previous_enabled' => $previousTwoFactorEnabled,
                    'current_enabled' => (bool) $settings->two_factor_enabled,
                    'previous_method' => $previousTwoFactorMethod,
                    'current_method' => $currentTwoFactorMethod,
                    'branch_id' => $branchId,
                ],
            );
        }

        return $settings;
    }
}

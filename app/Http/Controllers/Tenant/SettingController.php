<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UpdateTenantSettingRequest;
use App\Models\TenantSetting;
use App\Support\AuditTimelineLogger;
use DateTimeZone;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

final class SettingController extends Controller
{
    public function edit(): View
    {
        $settings = TenantSetting::query()
            ->where('branch_id', $this->currentBranchId())
            ->first();
        $timezones = DateTimeZone::listIdentifiers();

        return view('tenants.settings.edit', [
            'settings' => $settings,
            'timezones' => $timezones,
        ]);
    }

    public function update(UpdateTenantSettingRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $branchId = $this->currentBranchId();

        $settings = TenantSetting::query()->firstOrNew(['branch_id' => $branchId]);
        $settings->branch_id = $branchId;
        $previousTwoFactorEnabled = (bool) $settings->two_factor_enabled;
        $previousTwoFactorMethod = $settings->two_factor_method?->value;

        if ($request->hasFile('logo')) {
            $data['logo_path'] = (string) $request->file('logo')->store('tenant-settings', 'public');
        }

        if (! $request->boolean('two_factor_enabled')) {
            $data['two_factor_method'] = null;
        }

        unset($data['logo']);

        $settings->fill($data);
        $settings->save();

        $changedAttributes = array_keys($settings->getChanges());

        AuditTimelineLogger::log(
            event: 'settings_updated',
            description: 'Tenant settings updated.',
            causer: Auth::guard('user')->user(),
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
                causer: Auth::guard('user')->user(),
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

        return to_route('tenant.settings.edit')
            ->with('status', 'Settings updated.');
    }
}

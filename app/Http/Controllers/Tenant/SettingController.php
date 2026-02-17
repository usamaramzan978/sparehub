<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UpdateTenantSettingRequest;
use App\Models\TenantSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class SettingController extends Controller
{
    public function edit(): View
    {
        $settings = TenantSetting::query()
            ->where('branch_id', $this->currentBranchId())
            ->first();

        return view('tenants.settings.edit', [
            'settings' => $settings,
            'enableLocaleTimezone' => false,
            'locales' => [],
            'timezones' => [],
        ]);
    }

    public function update(UpdateTenantSettingRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $branchId = $this->currentBranchId();

        $settings = TenantSetting::query()->firstOrNew(['branch_id' => $branchId]);
        $settings->branch_id = $branchId;

        if ($request->hasFile('logo')) {
            $data['logo_path'] = (string) $request->file('logo')->store('tenant-settings', 'public');
        }

        unset($data['logo']);

        $settings->fill($data);
        $settings->save();

        return to_route('tenant.settings.edit')
            ->with('status', 'Settings updated.');
    }
}

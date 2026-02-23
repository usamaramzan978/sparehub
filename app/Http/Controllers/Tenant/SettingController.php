<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\Setting\UpsertTenantSettingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UpdateTenantSettingRequest;
use App\Models\TenantSetting;
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

    public function update(UpdateTenantSettingRequest $request, UpsertTenantSettingAction $action): RedirectResponse
    {
        $data = $request->validated();
        $branchId = $this->currentBranchId();

        if ($request->hasFile('logo')) {
            $data['logo_path'] = (string) $request->file('logo')->store('tenant-settings', 'public');
        }

        $action->handle($data, $branchId, $request->session(), Auth::guard('user')->user());

        return to_route('tenant.settings.edit')
            ->with('status', 'Settings updated.');
    }
}

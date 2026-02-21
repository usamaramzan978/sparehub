<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\TwoFactorMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UpdateTenantSettingRequest;
use App\Models\TenantSetting;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use DateTimeZone;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FA\Google2FA;

final class SettingController extends Controller
{
    public function edit(): View
    {
        $settings = TenantSetting::query()
            ->where('branch_id', $this->currentBranchId())
            ->first();
        $timezones = DateTimeZone::listIdentifiers();
        /** @var User|null $user */
        $user = Auth::guard('user')->user()?->fresh();
        [$authenticatorQrSvg, $authenticatorSecret, $showAuthenticatorSetup] = $this->buildAuthenticatorSetupData($settings, $user);

        return view('tenants.settings.edit', [
            'settings' => $settings,
            'timezones' => $timezones,
            'authenticatorQrSvg' => $authenticatorQrSvg,
            'authenticatorSecret' => $authenticatorSecret,
            'showAuthenticatorSetup' => $showAuthenticatorSetup,
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

        if (! $request->boolean('two_factor_enabled')) {
            $data['two_factor_method'] = null;
        }

        unset($data['logo']);

        $settings->fill($data);
        $settings->save();

        /** @var User|null $user */
        $user = Auth::guard('user')->user()?->fresh();
        if (
            $user
            && $settings->two_factor_enabled
            && $settings->two_factor_method === TwoFactorMethod::AUTHENTICATOR
            && ! $user->two_factor_secret
        ) {
            $google2fa = new Google2FA();
            $user->forceFill([
                'two_factor_type' => 'app',
                'two_factor_secret' => $google2fa->generateSecretKey(),
                'two_factor_verified_at' => null,
            ])->save();
        }

        return to_route('tenant.settings.edit')
            ->with('status', 'Settings updated.');
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: bool}
     */
    private function buildAuthenticatorSetupData(?TenantSetting $settings, ?User $user): array
    {
        if (! $settings || ! $user) {
            return [null, null, false];
        }

        if (! $settings->two_factor_enabled || $settings->two_factor_method !== TwoFactorMethod::AUTHENTICATOR) {
            return [null, null, false];
        }

        if (! $user->two_factor_secret) {
            return [null, null, false];
        }

        $google2fa = new Google2FA();
        $issuer = config('app.name', 'SpareHub');
        $qrCodeUrl = $google2fa->getQRCodeUrl($issuer, $user->email, $user->two_factor_secret);
        $renderer = new ImageRenderer(new RendererStyle(220), new SvgImageBackEnd());
        $writer = new Writer($renderer);

        return [
            $writer->writeString($qrCodeUrl),
            $user->two_factor_secret,
            $user->two_factor_verified_at === null,
        ];
    }
}

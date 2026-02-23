<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Profile;

use App\Enums\TwoFactorMethod;
use App\Models\TenantSetting;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

final class ProfileAuthenticatorServiceAction
{
    public function authenticatorEnabledForBranch(string $branchId): bool
    {
        $settings = TenantSetting::query()
            ->withoutGlobalScope('session_branch')
            ->where('branch_id', $branchId)
            ->first();

        return $settings?->two_factor_enabled && $settings->two_factor_method === TwoFactorMethod::AUTHENTICATOR;
    }

    /**
     * @return array{qr_svg: ?string, secret: ?string, needs_verification: bool, backup_codes_count: int}
     */
    public function buildSetupData(User $user): array
    {
        if (! $user->two_factor_secret) {
            return [
                'qr_svg' => null,
                'secret' => null,
                'needs_verification' => false,
                'backup_codes_count' => is_array($user->two_factor_recovery_codes) ? count($user->two_factor_recovery_codes) : 0,
            ];
        }

        $google2fa = new Google2FA();
        $issuer = config('app.name', 'SpareHub');
        $qrCodeUrl = $google2fa->getQRCodeUrl($issuer, $user->email, (string) $user->two_factor_secret);
        $renderer = new ImageRenderer(new RendererStyle(220), new SvgImageBackEnd());
        $writer = new Writer($renderer);

        return [
            'qr_svg' => $writer->writeString($qrCodeUrl),
            'secret' => (string) $user->two_factor_secret,
            'needs_verification' => $user->two_factor_verified_at === null,
            'backup_codes_count' => is_array($user->two_factor_recovery_codes) ? count($user->two_factor_recovery_codes) : 0,
        ];
    }

    /**
     * @return array{0: string, 1: array<int, string>, 2: array<int, string>}
     */
    public function generateCredentials(): array
    {
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();
        $backupCodes = collect(range(1, 8))
            ->map(fn (): string => mb_strtoupper(mb_substr(bin2hex(random_bytes(4)), 0, 4).'-'.mb_substr(bin2hex(random_bytes(4)), 0, 4)))
            ->all();
        $hashedBackupCodes = collect($backupCodes)
            ->map(fn (string $code): string => Hash::make($this->normalizeBackupCode($code)))
            ->all();

        return [$secret, $backupCodes, $hashedBackupCodes];
    }

    private function normalizeBackupCode(string $code): string
    {
        return mb_strtoupper(str_replace([' ', '-'], '', mb_trim($code)));
    }
}

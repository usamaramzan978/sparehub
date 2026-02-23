<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Cache;

final class HeaderContextCache
{
    public static function currentVersion(string $tenantId): int
    {
        if ($tenantId === '') {
            return 1;
        }

        return (int) Cache::get(self::versionKey($tenantId), 1);
    }

    public static function bumpForCurrentTenant(): void
    {
        $tenantId = self::resolveCurrentTenantId();

        if ($tenantId === '') {
            return;
        }

        $key = self::versionKey($tenantId);
        $currentVersion = (int) Cache::get($key, 1);

        Cache::forever($key, $currentVersion + 1);
    }

    private static function versionKey(string $tenantId): string
    {
        return sprintf('header_context_version:%s', $tenantId);
    }

    private static function resolveCurrentTenantId(): string
    {
        if (function_exists('tenant') && tenant()) {
            return (string) tenant()->getTenantKey();
        }

        return (string) request()->route('tenant', '');
    }
}

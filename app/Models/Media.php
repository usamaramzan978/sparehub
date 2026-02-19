<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Facades\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media as SpatieMedia;

final class Media extends SpatieMedia
{
    public function getConnectionName(): ?string
    {
        return config('tenancy.database.central_connection');
    }

    protected static function booted(): void
    {
        self::creating(function (self $media): void {
            if (! empty($media->tenant_id)) {
                return;
            }

            $tenantId = tenant()?->getTenantKey();
            if ($tenantId === null || $tenantId === '') {
                $tenantId = Request::route('tenant');
            }

            $media->tenant_id = is_string($tenantId) && $tenantId !== '' ? $tenantId : null;
        });
    }
}

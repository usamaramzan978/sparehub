<?php

declare(strict_types=1);

namespace App\Support\MediaLibrary;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

final class TenantPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        if ($media->collection_name === 'online_payment_proof') {
            return $this->tenantId().'/sale-payment-proofs/';
        }

        return $this->tenantId().'/'.$media->id.'/';
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getPath($media).'conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getPath($media).'responsive-images/';
    }

    private function tenantId(): string
    {
        return tenant()?->getTenantKey() ?? 'central';
    }
}

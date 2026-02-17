<?php

declare(strict_types=1);

namespace App\Support\MediaLibrary;

use Spatie\MediaLibrary\Support\UrlGenerator\DefaultUrlGenerator;

final class TenantAwareUrlGenerator extends DefaultUrlGenerator
{
    public function getUrl(): string
    {
        // asset() will now use the tenant domain automatically
        $url = asset($this->getPathRelativeToRoot());

        return $this->versionUrl($url);
    }
}

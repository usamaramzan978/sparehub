<?php

declare(strict_types=1);

namespace App\Models\Concerns;

trait UsesTenantConnection
{
    public function getConnectionName(): ?string
    {
        return 'tenant';
    }
}

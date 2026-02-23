<?php

declare(strict_types=1);

namespace App\Actions\Tenant\ServiceCatalog;

use App\Models\ServiceCatalog;

final class CreateServiceCatalogAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, string $branchId): ServiceCatalog
    {
        $payload['branch_id'] = $branchId;

        return ServiceCatalog::query()->create($payload);
    }
}

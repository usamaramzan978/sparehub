<?php

declare(strict_types=1);

namespace App\Actions\Tenant\ServiceCatalog;

use App\Models\ServiceCatalog;

final class UpdateServiceCatalogAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(ServiceCatalog $serviceCatalog, array $payload, string $branchId): bool
    {
        $payload['branch_id'] = $branchId;

        return $serviceCatalog->update($payload);
    }
}

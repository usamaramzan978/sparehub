<?php

declare(strict_types=1);

namespace App\Actions\Tenant\ServiceCatalog;

use App\Models\ServiceCatalog;

final class DeleteServiceCatalogAction
{
    public function handle(ServiceCatalog $serviceCatalog): bool
    {
        return (bool) $serviceCatalog->delete();
    }
}

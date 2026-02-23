<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Warehouse;

use App\Models\Warehouse;

final class DeleteWarehouseAction
{
    public function handle(Warehouse $warehouse): bool
    {
        return (bool) $warehouse->delete();
    }
}

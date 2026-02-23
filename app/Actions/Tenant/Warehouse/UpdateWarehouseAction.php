<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Warehouse;

use App\Models\Warehouse;

final class UpdateWarehouseAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Warehouse $warehouse, array $data): bool
    {
        return $warehouse->update($data);
    }
}

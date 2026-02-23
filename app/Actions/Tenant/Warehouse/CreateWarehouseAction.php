<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Warehouse;

use App\Models\Warehouse;

final class CreateWarehouseAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Warehouse
    {
        return Warehouse::query()->create($data);
    }
}

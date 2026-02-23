<?php

declare(strict_types=1);

namespace App\Actions\Tenant\ProductPrice;

use App\Models\ProductPrice;

final class CreateProductPriceAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, string $branchId): ProductPrice
    {
        $payload['branch_id'] = $branchId;

        return ProductPrice::query()->create($payload);
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Tenant\ProductPrice;

use App\Models\ProductPrice;

final class UpdateProductPriceAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(ProductPrice $productPrice, array $payload, string $branchId): bool
    {
        $payload['branch_id'] = $branchId;

        return $productPrice->update($payload);
    }
}

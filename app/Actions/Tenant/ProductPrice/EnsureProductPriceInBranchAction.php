<?php

declare(strict_types=1);

namespace App\Actions\Tenant\ProductPrice;

use App\Models\ProductPrice;

final class EnsureProductPriceInBranchAction
{
    public function handle(ProductPrice $productPrice, string $branchId): ProductPrice
    {
        abort_if($productPrice->branch_id !== $branchId, 404);

        return $productPrice;
    }
}

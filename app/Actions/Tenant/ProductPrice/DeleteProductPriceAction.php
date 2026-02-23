<?php

declare(strict_types=1);

namespace App\Actions\Tenant\ProductPrice;

use App\Models\ProductPrice;

final class DeleteProductPriceAction
{
    public function handle(ProductPrice $productPrice): bool
    {
        return (bool) $productPrice->delete();
    }
}

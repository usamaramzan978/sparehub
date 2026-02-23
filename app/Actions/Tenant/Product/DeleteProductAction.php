<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Product;

use App\Models\Product;

final class DeleteProductAction
{
    public function handle(Product $product): bool
    {
        return (bool) $product->delete();
    }
}

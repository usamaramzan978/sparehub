<?php

declare(strict_types=1);

namespace App\Actions\Tenant\CodeGenerator;

use App\Models\Product;

final class DeleteProductQrAction
{
    public function handle(Product $product): bool
    {
        return $product->update(['qrcode' => null]);
    }
}

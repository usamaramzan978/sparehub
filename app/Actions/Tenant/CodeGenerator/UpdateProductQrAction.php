<?php

declare(strict_types=1);

namespace App\Actions\Tenant\CodeGenerator;

use App\Models\Product;

final class UpdateProductQrAction
{
    public function handle(Product $product, string $value): bool
    {
        return $product->update(['qrcode' => $value]);
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Product;

use App\Models\Product;

final readonly class CreateProductAction
{
    public function __construct(private SyncProductOpeningStockAction $syncProductOpeningStockAction) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, string $branchId): Product
    {
        $openingStock = (float) ($payload['opening_stock'] ?? 0);
        unset($payload['opening_stock']);

        return Product::query()->getConnection()->transaction(function () use ($payload, $branchId, $openingStock): Product {
            $product = Product::query()->create($payload);
            $this->syncProductOpeningStockAction->handle($product, $branchId, $openingStock, isNewProduct: true);

            return $product;
        });
    }
}

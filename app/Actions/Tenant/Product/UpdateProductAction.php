<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Product;

use App\Models\Product;

final readonly class UpdateProductAction
{
    public function __construct(private SyncProductOpeningStockAction $syncProductOpeningStockAction) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(Product $product, array $payload, string $branchId): Product
    {
        $openingStock = (float) ($payload['opening_stock'] ?? 0);
        unset($payload['opening_stock']);

        Product::query()->getConnection()->transaction(function () use ($product, $payload, $branchId, $openingStock): void {
            $product->update($payload);
            $this->syncProductOpeningStockAction->handle($product, $branchId, $openingStock);
        });

        return $product;
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Product;

use App\Models\Product;

final readonly class UpdateProductAction
{
    public function __construct(
        private SyncProductOpeningStockAction $syncProductOpeningStockAction,
        private SyncProductPriceAction $syncProductPriceAction
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(Product $product, array $payload, string $branchId): Product
    {
        $openingStock = (float) ($payload['opening_stock'] ?? 0);
        $pricePayload = [
            'cost' => $payload['cost'] ?? 0,
            'mrp' => $payload['mrp'] ?? 0,
            'retail_price' => $payload['retail_price'] ?? 0,
            'wholesale_price' => $payload['wholesale_price'] ?? 0,
            'effective_from' => $payload['effective_from'] ?? null,
        ];

        unset($payload['opening_stock']);
        unset($payload['cost'], $payload['mrp'], $payload['retail_price'], $payload['wholesale_price'], $payload['effective_from']);

        Product::query()->getConnection()->transaction(function () use ($product, $payload, $branchId, $openingStock, $pricePayload): void {
            $product->update($payload);
            $this->syncProductOpeningStockAction->handle($product, $branchId, $openingStock);
            $this->syncProductPriceAction->handle($product, $pricePayload, $branchId);
        });

        return $product;
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Product;

use App\Models\Product;

final readonly class CreateProductAction
{
    public function __construct(
        private SyncProductOpeningStockAction $syncProductOpeningStockAction,
        private SyncProductPriceAction $syncProductPriceAction
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, string $branchId): Product
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

        return Product::query()->getConnection()->transaction(function () use ($payload, $branchId, $openingStock, $pricePayload): Product {
            $product = Product::query()->create($payload);
            $this->syncProductOpeningStockAction->handle($product, $branchId, $openingStock, isNewProduct: true);
            $this->syncProductPriceAction->handle($product, $pricePayload, $branchId);

            return $product;
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Product;

use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\ProductPrice;

final class GetProductDetailsAction
{
    /**
     * @return array<string, mixed>
     */
    public function handle(Product $product, string $branchId): array
    {
        $product->load(['category', 'brand', 'defaultTax', 'defaultUnit']);

        $priceHistory = ProductPrice::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $product->id)
            ->latest('effective_from')
            ->get();

        $latestPrice = $priceHistory->first();

        $stockOnHand = (float) InventoryStock::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $product->id)
            ->sum('qty_on_hand');

        $stockReserved = (float) InventoryStock::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $product->id)
            ->sum('qty_reserved');

        $priceRows = $priceHistory->map(fn (ProductPrice $price): array => [
            'name' => $product->name,
            'sku' => $product->sku,
            'on_hand' => $stockOnHand,
            'cost' => (float) $price->cost,
            'sale' => (float) $price->retail_price,
            'status' => $product->status->value,
            'effective_from' => $price->effective_from?->format('Y-m-d H:i'),
        ]);

        return [
            'product' => $product,
            'latestPrice' => $latestPrice,
            'stockOnHand' => $stockOnHand,
            'stockReserved' => $stockReserved,
            'priceRows' => $priceRows,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Tenant\ProductStock;

use App\Enums\RecordStatus;
use App\Enums\StockMoveType;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\StockMove;
use Illuminate\Support\Collection;

final class ListAdjustableProductsAction
{
    /**
     * @return Collection<int, Product>
     */
    public function handle(string $branchId): Collection
    {
        $products = Product::query()
            ->where('track_stock', true)
            ->where('status', RecordStatus::ACTIVE->value)
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        $openingByProduct = StockMove::query()
            ->where('branch_id', $branchId)
            ->whereIn('product_id', $products->pluck('id')->all())
            ->where('move_type', StockMoveType::OPENING->value)
            ->selectRaw('product_id, SUM(qty) as opening_qty')
            ->groupBy('product_id')
            ->pluck('opening_qty', 'product_id');

        $firstMoveByProduct = StockMove::query()
            ->where('branch_id', $branchId)
            ->whereIn('product_id', $products->pluck('id')->all())
            ->oldest('occurred_at')->oldest()
            ->get(['product_id', 'qty'])
            ->groupBy('product_id')
            ->map(fn (Collection $moves): float => (float) $moves->first()?->qty ?: 0.0);

        $stockByProduct = InventoryStock::query()
            ->where('branch_id', $branchId)
            ->whereIn('product_id', $products->pluck('id')->all())
            ->selectRaw('product_id, SUM(qty_on_hand - qty_reserved) as qty_available')
            ->groupBy('product_id')
            ->pluck('qty_available', 'product_id');

        $onHandByProduct = InventoryStock::query()
            ->where('branch_id', $branchId)
            ->whereIn('product_id', $products->pluck('id')->all())
            ->selectRaw('product_id, SUM(qty_on_hand) as qty_on_hand')
            ->groupBy('product_id')
            ->pluck('qty_on_hand', 'product_id');

        return $products->map(function (Product $product) use ($firstMoveByProduct, $onHandByProduct, $openingByProduct, $stockByProduct): Product {
            $qtyOnHand = (float) ($onHandByProduct[$product->id] ?? 0.0);
            $product->setAttribute('qty_available', (float) ($stockByProduct[$product->id] ?? 0.0));
            $openingFromMove = (float) ($openingByProduct[$product->id] ?? 0.0);
            $openingFromFirstMove = (float) ($firstMoveByProduct[$product->id] ?? 0.0);
            $openingStock = $openingFromMove > 0 ? $openingFromMove : ($openingFromFirstMove > 0 ? $openingFromFirstMove : $qtyOnHand);
            $product->setAttribute('opening_stock', $openingStock);

            return $product;
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Sale;

use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\SaleItem;
use Illuminate\Support\Collection;

final class SyncSaleItemStocksAction
{
    /**
     * @param  Collection<int, SaleItem>  $saleItems
     */
    public function handle(Collection $saleItems, string $branchId, bool $reverse = false): void
    {
        $qtyByProduct = $saleItems
            ->filter(fn (SaleItem $item): bool => $item->product_id !== null)
            ->groupBy('product_id')
            ->map(fn (Collection $items): float => (float) $items->sum('qty'));

        if ($qtyByProduct->isEmpty()) {
            return;
        }

        $trackedProductIds = Product::query()
            ->whereIn('id', $qtyByProduct->keys()->all())
            ->where('track_stock', true)
            ->pluck('id')
            ->all();

        if ($trackedProductIds === []) {
            return;
        }

        foreach ($trackedProductIds as $productId) {
            $qty = (float) ($qtyByProduct->get($productId) ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $stockRow = InventoryStock::query()
                ->where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->oldest('updated_at')
                ->first();

            if (! $stockRow instanceof InventoryStock) {
                continue;
            }

            $adjustedQty = $reverse
                ? (float) $stockRow->qty_on_hand + $qty
                : (float) $stockRow->qty_on_hand - $qty;

            $stockRow->update([
                'qty_on_hand' => $adjustedQty,
            ]);
        }
    }
}

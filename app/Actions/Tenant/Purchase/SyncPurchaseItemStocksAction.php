<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Purchase;

use App\Enums\StockMoveType;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\StockMove;
use Illuminate\Support\Collection;

final class SyncPurchaseItemStocksAction
{
    /**
     * @param  Collection<int, PurchaseItem>  $purchaseItems
     */
    public function handle(Collection $purchaseItems, string $branchId, bool $reverse = false): void
    {
        $qtyByProduct = $purchaseItems
            ->filter(fn (PurchaseItem $item): bool => $item->product_id !== null)
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
                ->first();

            if (! $stockRow instanceof InventoryStock) {
                if ($reverse) {
                    continue;
                }

                $stockRow = InventoryStock::query()->create([
                    'branch_id' => $branchId,
                    'product_id' => $productId,
                    'qty_on_hand' => 0,
                    'qty_reserved' => 0,
                    'avg_cost' => 0,
                ]);
            }

            $adjustedQty = $reverse
                ? (float) $stockRow->qty_on_hand - $qty
                : (float) $stockRow->qty_on_hand + $qty;

            $stockRow->update([
                'qty_on_hand' => $adjustedQty,
            ]);

            StockMove::query()->create([
                'product_id' => $productId,
                'branch_id' => $branchId,
                'warehouse_id' => null,
                'created_by' => auth('user')->id(),
                'move_type' => $reverse ? StockMoveType::PURCHASE_RETURN->value : StockMoveType::PURCHASE->value,
                'qty' => $qty,
                'unit_cost' => 0,
                'total_cost' => 0,
                'reference_type' => null,
                'reference_id' => null,
                'remarks' => $reverse ? 'Stock reversed from purchase change/delete.' : 'Stock increased from purchase.',
                'occurred_at' => now(),
            ]);
        }
    }
}

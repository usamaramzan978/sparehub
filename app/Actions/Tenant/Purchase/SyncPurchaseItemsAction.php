<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Purchase;

use App\Actions\Tenant\Product\SyncProductPriceAction;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;

final readonly class SyncPurchaseItemsAction
{
    private const AUTO_PRICE_SYNC_NOTE = 'Stock qty updated and product prices synced from this purchase item.';

    public function __construct(
        private SyncPurchaseItemStocksAction $syncPurchaseItemStocksAction,
        private SyncProductPriceAction $syncProductPriceAction
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function handle(Purchase $purchase, array $items, string $branchId): void
    {
        $existingItems = $purchase->items()->get();
        $this->syncPurchaseItemStocksAction->handle($existingItems, $branchId, reverse: true);
        $purchase->items()->delete();
        $products = Product::query()
            ->whereIn('id', array_column($items, 'product_id'))
            ->get()
            ->keyBy('id');

        foreach ($items as $item) {
            $qty = (float) $item['qty'];
            $cost = (float) $item['cost'];
            $mrp = (float) $item['mrp'];
            $retailPrice = (float) $item['retail_price'];
            $wholesalePrice = (float) $item['wholesale_price'];
            $lineTotal = $qty * $cost;

            PurchaseItem::query()->create([
                'purchase_id' => $purchase->id,
                'branch_id' => $branchId,
                'product_id' => $item['product_id'],
                'qty' => $qty,
                'received_qty' => $qty,
                'unit_cost' => $cost,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'line_total' => $lineTotal,
                'remarks' => self::AUTO_PRICE_SYNC_NOTE,
            ]);

            $product = $products->get($item['product_id']);
            if ($product instanceof Product) {
                $this->syncProductPriceAction->handle($product, [
                    'cost' => $cost,
                    'mrp' => $mrp,
                    'retail_price' => $retailPrice,
                    'wholesale_price' => $wholesalePrice,
                    'effective_from' => $purchase->purchase_date,
                ], $branchId);
            }
        }

        $createdItems = $purchase->items()->get();
        $this->syncPurchaseItemStocksAction->handle($createdItems, $branchId);
        $grandTotal = (float) $createdItems->sum(fn (PurchaseItem $item): float => (float) $item->line_total);

        $purchase->update([
            'grand_total' => $grandTotal,
        ]);
    }
}

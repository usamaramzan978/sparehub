<?php

declare(strict_types=1);

namespace App\Actions\Tenant\PurchaseReturn;

use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;

final readonly class SyncPurchaseReturnItemsAction
{
    public function __construct(private SyncPurchaseReturnItemStocksAction $syncPurchaseReturnItemStocksAction) {}

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function handle(PurchaseReturn $purchaseReturn, array $items): void
    {
        $existingItems = $purchaseReturn->items()->get();
        $this->syncPurchaseReturnItemStocksAction->handle($existingItems, $purchaseReturn->branch_id, reverse: true);
        $purchaseReturn->items()->delete();

        foreach ($items as $item) {
            $qty = (float) $item['qty'];
            $unitCost = (float) $item['unit_cost'];
            $lineTotal = $qty * $unitCost;

            PurchaseReturnItem::query()->create([
                'purchase_return_id' => $purchaseReturn->id,
                'purchase_item_id' => ($item['purchase_item_id'] ?? null) ?: null,
                'product_id' => $item['product_id'],
                'qty' => $qty,
                'unit_cost' => $unitCost,
                'line_total' => $lineTotal,
            ]);
        }

        $createdItems = $purchaseReturn->items()->get();
        $this->syncPurchaseReturnItemStocksAction->handle($createdItems, $purchaseReturn->branch_id);
        $grandTotal = (float) $createdItems->sum(fn (PurchaseReturnItem $item): float => (float) $item->line_total);

        $purchaseReturn->update([
            'grand_total' => $grandTotal,
        ]);
    }
}

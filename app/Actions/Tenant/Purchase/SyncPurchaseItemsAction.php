<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Purchase;

use App\Models\Purchase;
use App\Models\PurchaseItem;

final readonly class SyncPurchaseItemsAction
{
    public function __construct(private SyncPurchaseItemStocksAction $syncPurchaseItemStocksAction) {}

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function handle(Purchase $purchase, array $items, string $branchId): void
    {
        $existingItems = $purchase->items()->get();
        $this->syncPurchaseItemStocksAction->handle($existingItems, $branchId, reverse: true);
        $purchase->items()->delete();

        foreach ($items as $item) {
            $qty = (float) $item['qty'];
            $unitCost = (float) $item['unit_cost'];
            $discountAmount = (float) ($item['discount_amount'] ?? 0);
            $taxAmount = (float) ($item['tax_amount'] ?? 0);
            $lineTotal = ($qty * $unitCost) - $discountAmount + $taxAmount;

            PurchaseItem::query()->create([
                'purchase_id' => $purchase->id,
                'branch_id' => $branchId,
                'product_id' => $item['product_id'],
                'tax_id' => $item['tax_id'] ?? null,
                'qty' => $qty,
                'received_qty' => $qty,
                'unit_cost' => $unitCost,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'line_total' => $lineTotal,
                'remarks' => $item['remarks'] ?? null,
            ]);
        }

        $createdItems = $purchase->items()->get();
        $this->syncPurchaseItemStocksAction->handle($createdItems, $branchId);
        $subTotal = (float) $createdItems->sum(fn (PurchaseItem $item): float => (float) $item->qty * (float) $item->unit_cost);
        $discountTotal = (float) $createdItems->sum(fn (PurchaseItem $item): float => (float) $item->discount_amount);
        $taxTotal = (float) $createdItems->sum(fn (PurchaseItem $item): float => (float) $item->tax_amount);
        $shippingTotal = (float) $purchase->shipping_total;
        $grandTotal = $subTotal - $discountTotal + $taxTotal + $shippingTotal;
        $paidTotal = (float) $purchase->paid_total;

        $purchase->update([
            'sub_total' => $subTotal,
            'discount_total' => $discountTotal,
            'tax_total' => $taxTotal,
            'grand_total' => $grandTotal,
            'balance_due' => $grandTotal - $paidTotal,
        ]);
    }
}

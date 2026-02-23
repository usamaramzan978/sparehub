<?php

declare(strict_types=1);

namespace App\Actions\Tenant\PurchaseReturnItem;

use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;

final class RecalculatePurchaseReturnTotalsAction
{
    public function handle(?PurchaseReturn $purchaseReturn): void
    {
        if (! $purchaseReturn instanceof PurchaseReturn) {
            return;
        }

        $items = $purchaseReturn->items()->get();
        $subTotal = (float) $items->sum(fn (PurchaseReturnItem $item): float => (float) $item->qty * (float) $item->unit_cost);
        $taxTotal = (float) $items->sum(fn (PurchaseReturnItem $item): float => (float) $item->tax_amount);
        $grandTotal = (float) $items->sum(fn (PurchaseReturnItem $item): float => (float) $item->line_total);

        $purchaseReturn->update([
            'sub_total' => $subTotal,
            'tax_total' => $taxTotal,
            'grand_total' => $grandTotal,
        ]);
    }
}

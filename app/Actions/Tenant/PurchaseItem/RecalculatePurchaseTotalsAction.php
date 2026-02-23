<?php

declare(strict_types=1);

namespace App\Actions\Tenant\PurchaseItem;

use App\Models\Purchase;
use App\Models\PurchaseItem;

final class RecalculatePurchaseTotalsAction
{
    public function handle(?Purchase $purchase): void
    {
        if (! $purchase instanceof Purchase) {
            return;
        }

        $items = $purchase->items()->get();
        $subTotal = (float) $items->sum(fn (PurchaseItem $item): float => (float) $item->qty * (float) $item->unit_cost);
        $discountTotal = (float) $items->sum(fn (PurchaseItem $item): float => (float) $item->discount_amount);
        $taxTotal = (float) $items->sum(fn (PurchaseItem $item): float => (float) $item->tax_amount);
        $shippingTotal = (float) $purchase->shipping_total;
        $grandTotal = ($subTotal - $discountTotal + $taxTotal) + $shippingTotal;
        $paidTotal = (float) $purchase->payments()->sum('amount');

        $purchase->update([
            'sub_total' => $subTotal,
            'discount_total' => $discountTotal,
            'tax_total' => $taxTotal,
            'grand_total' => $grandTotal,
            'paid_total' => $paidTotal,
            'balance_due' => $grandTotal - $paidTotal,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Tenant\SaleItem;

use App\Models\Sale;
use App\Models\SaleItem;

final class RecalculateSaleTotalsAction
{
    public function handle(?Sale $sale): void
    {
        if (! $sale instanceof Sale) {
            return;
        }

        $items = $sale->items()->get();
        $subTotal = (float) $items->sum(fn (SaleItem $item): float => (float) $item->qty * (float) $item->unit_price);
        $discountTotal = (float) $items->sum(fn (SaleItem $item): float => (float) $item->discount_amount);
        $taxTotal = (float) $items->sum(fn (SaleItem $item): float => (float) $item->tax_amount);
        $grandTotal = (float) $items->sum(fn (SaleItem $item): float => (float) $item->line_total);
        $paidTotal = (float) $sale->payments()->sum('amount');

        $sale->update([
            'sub_total' => $subTotal,
            'discount_total' => $discountTotal,
            'tax_total' => $taxTotal,
            'grand_total' => $grandTotal,
            'paid_total' => $paidTotal,
            'balance_due' => $grandTotal - $paidTotal,
        ]);
    }
}

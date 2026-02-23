<?php

declare(strict_types=1);

namespace App\Actions\Tenant\SalePayment;

use App\Models\Sale;

final class RecalculateSalePaidTotalsAction
{
    public function handle(?Sale $sale): void
    {
        if (! $sale instanceof Sale) {
            return;
        }

        $paid = (float) $sale->payments()->sum('amount');
        $grandTotal = (float) $sale->grand_total;

        $sale->update([
            'paid_total' => $paid,
            'balance_due' => $grandTotal - $paid,
        ]);
    }
}

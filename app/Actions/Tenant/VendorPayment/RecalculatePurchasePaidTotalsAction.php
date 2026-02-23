<?php

declare(strict_types=1);

namespace App\Actions\Tenant\VendorPayment;

use App\Models\Purchase;

final class RecalculatePurchasePaidTotalsAction
{
    public function handle(?Purchase $purchase): void
    {
        if (! $purchase instanceof Purchase) {
            return;
        }

        $paid = (float) $purchase->payments()->sum('amount');
        $grandTotal = (float) $purchase->grand_total;

        $purchase->update([
            'paid_total' => $paid,
            'balance_due' => $grandTotal - $paid,
        ]);
    }
}

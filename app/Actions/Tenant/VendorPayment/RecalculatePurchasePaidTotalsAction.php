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
    }
}

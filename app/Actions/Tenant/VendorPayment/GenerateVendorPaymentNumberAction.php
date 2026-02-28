<?php

declare(strict_types=1);

namespace App\Actions\Tenant\VendorPayment;

use App\Models\VendorPayment;

final class GenerateVendorPaymentNumberAction
{
    public function handle(string $branchId): string
    {
        $datePrefix = now()->format('Ymd');
        $base = 'VP-'.$datePrefix.'-';

        do {
            $last = VendorPayment::query()
                ->withoutGlobalScopes()
                ->where('branch_id', $branchId)
                ->where('payment_no', 'like', $base.'%')
                ->orderByDesc('payment_no')
                ->value('payment_no');

            $next = 1;
            if (is_string($last)) {
                $suffix = (int) mb_substr($last, mb_strlen($base));
                $next = $suffix + 1;
            }

            $paymentNumber = $base.mb_str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $exists = VendorPayment::query()
                ->withoutGlobalScopes()
                ->where('branch_id', $branchId)
                ->where('payment_no', $paymentNumber)
                ->exists();
        } while ($exists);

        return $paymentNumber;
    }
}

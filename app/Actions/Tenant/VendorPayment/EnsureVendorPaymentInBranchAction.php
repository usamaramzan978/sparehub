<?php

declare(strict_types=1);

namespace App\Actions\Tenant\VendorPayment;

use App\Models\VendorPayment;

final class EnsureVendorPaymentInBranchAction
{
    public function handle(VendorPayment $vendorPayment, string $branchId): VendorPayment
    {
        abort_if($vendorPayment->branch_id !== $branchId, 404);

        return $vendorPayment;
    }
}

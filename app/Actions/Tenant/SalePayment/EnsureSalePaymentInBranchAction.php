<?php

declare(strict_types=1);

namespace App\Actions\Tenant\SalePayment;

use App\Models\SalePayment;

final class EnsureSalePaymentInBranchAction
{
    public function handle(SalePayment $salePayment, string $branchId): SalePayment
    {
        abort_if($salePayment->branch_id !== $branchId, 404);

        return $salePayment;
    }
}

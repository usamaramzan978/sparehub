<?php

declare(strict_types=1);

namespace App\Actions\Tenant\SaleHold;

use App\Models\SaleHold;

final class EnsureSaleHoldInBranchAction
{
    public function handle(SaleHold $saleHold, string $branchId): SaleHold
    {
        abort_if($saleHold->branch_id !== $branchId, 404);

        return $saleHold;
    }
}

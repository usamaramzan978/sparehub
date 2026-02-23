<?php

declare(strict_types=1);

namespace App\Actions\Tenant\SaleItem;

use App\Models\SaleItem;

final class EnsureSaleItemInBranchAction
{
    public function handle(SaleItem $saleItem, string $branchId): SaleItem
    {
        abort_if($saleItem->branch_id !== $branchId, 404);

        return $saleItem;
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Tenant\PurchaseReturnItem;

use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;

final class EnsurePurchaseReturnItemInBranchAction
{
    public function handle(PurchaseReturnItem $purchaseReturnItem, string $branchId): PurchaseReturnItem
    {
        $purchaseReturn = $purchaseReturnItem->purchaseReturn;
        abort_if(! $purchaseReturn instanceof PurchaseReturn || $purchaseReturn->branch_id !== $branchId, 404);

        return $purchaseReturnItem;
    }
}

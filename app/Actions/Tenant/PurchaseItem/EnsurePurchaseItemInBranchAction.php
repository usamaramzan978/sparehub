<?php

declare(strict_types=1);

namespace App\Actions\Tenant\PurchaseItem;

use App\Models\PurchaseItem;

final class EnsurePurchaseItemInBranchAction
{
    public function handle(PurchaseItem $purchaseItem, string $branchId): PurchaseItem
    {
        abort_if($purchaseItem->branch_id !== $branchId, 404);

        return $purchaseItem;
    }
}

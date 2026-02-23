<?php

declare(strict_types=1);

namespace App\Actions\Tenant\PurchaseReturn;

use App\Models\PurchaseReturn;

final class EnsurePurchaseReturnInBranchAction
{
    public function handle(PurchaseReturn $purchaseReturn, string $branchId): PurchaseReturn
    {
        abort_if($purchaseReturn->branch_id !== $branchId, 404);

        return $purchaseReturn;
    }
}

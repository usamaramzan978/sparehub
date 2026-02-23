<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Purchase;

use App\Models\Purchase;

final class EnsurePurchaseInBranchAction
{
    public function handle(Purchase $purchase, string $branchId): Purchase
    {
        abort_if($purchase->branch_id !== $branchId, 404);

        return $purchase;
    }
}

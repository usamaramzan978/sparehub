<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Sale;

use App\Models\Sale;

final class EnsureSaleInBranchAction
{
    public function handle(Sale $sale, string $branchId): Sale
    {
        abort_if($sale->branch_id !== $branchId, 404);

        return $sale;
    }
}

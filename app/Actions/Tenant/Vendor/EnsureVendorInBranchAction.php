<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Vendor;

use App\Models\Vendor;

final class EnsureVendorInBranchAction
{
    public function handle(Vendor $vendor, string $branchId): Vendor
    {
        abort_if($vendor->branch_id !== $branchId, 404);

        return $vendor;
    }
}

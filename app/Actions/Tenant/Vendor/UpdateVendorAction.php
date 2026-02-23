<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Vendor;

use App\Models\Vendor;

final class UpdateVendorAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(Vendor $vendor, array $payload, string $branchId): bool
    {
        $payload['branch_id'] = $branchId;

        return $vendor->update($payload);
    }
}

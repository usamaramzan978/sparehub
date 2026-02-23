<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Vendor;

use App\Models\Vendor;

final class CreateVendorAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, string $branchId): Vendor
    {
        $payload['branch_id'] = $branchId;

        return Vendor::query()->create($payload);
    }
}

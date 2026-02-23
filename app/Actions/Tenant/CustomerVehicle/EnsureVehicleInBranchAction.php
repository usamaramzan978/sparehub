<?php

declare(strict_types=1);

namespace App\Actions\Tenant\CustomerVehicle;

use App\Models\Customer;
use App\Models\CustomerVehicle;

final class EnsureVehicleInBranchAction
{
    public function handle(CustomerVehicle $customerVehicle, string $branchId): CustomerVehicle
    {
        $customer = $customerVehicle->customer;

        abort_if(! $customer instanceof Customer || $customer->branch_id !== $branchId, 404);

        return $customerVehicle;
    }
}

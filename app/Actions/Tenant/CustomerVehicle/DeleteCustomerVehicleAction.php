<?php

declare(strict_types=1);

namespace App\Actions\Tenant\CustomerVehicle;

use App\Models\CustomerVehicle;

final class DeleteCustomerVehicleAction
{
    public function handle(CustomerVehicle $customerVehicle): bool
    {
        return (bool) $customerVehicle->delete();
    }
}

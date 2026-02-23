<?php

declare(strict_types=1);

namespace App\Actions\Tenant\CustomerVehicle;

use App\Models\CustomerVehicle;

final class UpdateCustomerVehicleAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(CustomerVehicle $customerVehicle, array $data): bool
    {
        return $customerVehicle->update($data);
    }
}

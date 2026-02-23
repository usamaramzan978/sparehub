<?php

declare(strict_types=1);

namespace App\Actions\Tenant\CustomerVehicle;

use App\Models\CustomerVehicle;

final class CreateCustomerVehicleAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): CustomerVehicle
    {
        return CustomerVehicle::query()->create($data);
    }
}

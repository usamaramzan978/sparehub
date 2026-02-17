<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Customer;

use App\Models\Customer;

final class CreateCustomerAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Customer
    {
        return Customer::query()->create($data);
    }
}

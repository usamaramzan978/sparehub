<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Customer;

use App\Models\Customer;

final class UpdateCustomerAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Customer $customer, array $data): bool
    {
        return $customer->update($data);
    }
}

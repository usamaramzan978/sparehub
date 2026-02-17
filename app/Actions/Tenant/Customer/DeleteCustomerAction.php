<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Customer;

use App\Models\Customer;

final class DeleteCustomerAction
{
    public function handle(Customer $customer): bool
    {
        return (bool) $customer->delete();
    }
}

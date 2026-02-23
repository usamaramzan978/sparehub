<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Customer;

use App\Models\Customer;

final class EnsureCustomerInBranchAction
{
    public function handle(Customer $customer, string $branchId): Customer
    {
        abort_if($customer->branch_id !== $branchId, 404);

        return $customer;
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Customer;

use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Support\Collection;

final class ListRecentCustomerSalesAction
{
    /**
     * @return Collection<int, Sale>
     */
    public function handle(Customer $customer, string $branchId, int $limit = 10): Collection
    {
        return Sale::query()
            ->where('branch_id', $branchId)
            ->where('customer_id', $customer->id)
            ->latest('invoice_date')
            ->limit($limit)
            ->get();
    }
}

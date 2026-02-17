<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\Customer\CreateCustomerAction;
use App\Actions\Tenant\Customer\DeleteCustomerAction;
use App\Actions\Tenant\Customer\UpdateCustomerAction;
use App\Enums\CustomerStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CustomerRequest;
use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = min(max($request->integer('per_page', 15), 5), 100);

        $customers = Customer::query()
            ->latest()
            ->paginate($perPage);

        return view('tenants.customers.index', ['items' => $customers]);
    }

    public function store(CustomerRequest $request, CreateCustomerAction $action): RedirectResponse
    {
        $data = $request->validated();
        $data['branch_id'] = $this->currentBranchId();
        $action->handle($data);

        return to_route('tenant.customers.index')
            ->with('status', 'Created.');
    }

    public function create(): View
    {
        $statuses = CustomerStatus::cases();

        return view('tenants.customers.create', [
            'statuses' => $statuses,
        ]);
    }

    public function show(Customer $customer): View
    {
        $this->ensureCustomerInCurrentBranch($customer);

        $customer->load(['branch', 'vehicles']);
        $recentSales = Sale::query()
            ->where('branch_id', $this->currentBranchId())
            ->where('customer_id', $customer->id)
            ->latest('invoice_date')
            ->limit(10)
            ->get();

        return view('tenants.customers.show', [
            'customer' => $customer,
            'recentSales' => $recentSales,
        ]);
    }

    public function update(CustomerRequest $request, Customer $customer, UpdateCustomerAction $action): RedirectResponse
    {
        $this->ensureCustomerInCurrentBranch($customer);

        $data = $request->validated();
        $data['branch_id'] = $this->currentBranchId();
        $action->handle($customer, $data);

        return to_route('tenant.customers.index')
            ->with('status', 'Updated.');
    }

    public function edit(Customer $customer): View
    {
        $this->ensureCustomerInCurrentBranch($customer);

        $statuses = CustomerStatus::cases();

        return view('tenants.customers.edit', [
            'customer' => $customer,
            'statuses' => $statuses,
        ]);
    }

    public function destroy(Customer $customer, DeleteCustomerAction $action): RedirectResponse
    {
        $this->ensureCustomerInCurrentBranch($customer);

        $action->handle($customer);

        return to_route('tenant.customers.index')
            ->with('status', 'Deleted.');
    }

    private function ensureCustomerInCurrentBranch(Customer $customer): void
    {
        abort_if($customer->branch_id !== $this->currentBranchId(), 404);
    }
}

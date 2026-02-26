<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\Customer\CreateCustomerAction;
use App\Actions\Tenant\Customer\DeleteCustomerAction;
use App\Actions\Tenant\Customer\EnsureCustomerInBranchAction;
use App\Actions\Tenant\Customer\ListRecentCustomerSalesAction;
use App\Actions\Tenant\Customer\UpdateCustomerAction;
use App\Enums\CustomerStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CustomerRequest;
use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = min(max($request->integer('per_page', 15), 5), 100);
        $search = mb_trim($request->string('search')->toString());
        $sortBy = $request->string('sort_by')->toString();
        $sortDirection = $request->string('sort_direction')->toString();
        $allowedSortColumns = ['code', 'name', 'phone', 'email', 'status', 'created_at'];
        $activeSortBy = in_array($sortBy, $allowedSortColumns, true) ? $sortBy : null;
        $activeSortDirection = in_array($sortDirection, ['asc', 'desc'], true) ? $sortDirection : 'asc';

        $customersQuery = Customer::query()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('code', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('phone', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('email', 'like', sprintf('%%%s%%', $search));
                });
            });

        if ($activeSortBy !== null) {
            $customersQuery->orderBy($activeSortBy, $activeSortDirection);
        } else {
            $customersQuery->latest();
        }

        $customers = $customersQuery
            ->paginate($perPage)
            ->withQueryString();

        return view('tenants.customers.index', [
            'items' => $customers,
            'sortBy' => $activeSortBy,
            'sortDirection' => $activeSortDirection,
        ]);
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

    public function show(
        Customer $customer,
        EnsureCustomerInBranchAction $ensureCustomerInBranchAction,
        ListRecentCustomerSalesAction $listRecentCustomerSalesAction
    ): View {
        $customer = $ensureCustomerInBranchAction->handle($customer, $this->currentBranchId());

        $customer->load(['branch', 'vehicles']);

        $recentSales = $listRecentCustomerSalesAction->handle($customer, $this->currentBranchId());

        return view('tenants.customers.show', [
            'customer' => $customer,
            'recentSales' => $recentSales,
        ]);
    }

    public function update(
        CustomerRequest $request,
        Customer $customer,
        UpdateCustomerAction $action,
        EnsureCustomerInBranchAction $ensureCustomerInBranchAction
    ): RedirectResponse {
        $customer = $ensureCustomerInBranchAction->handle($customer, $this->currentBranchId());

        $data = $request->validated();
        $data['branch_id'] = $this->currentBranchId();
        $action->handle($customer, $data);

        return to_route('tenant.customers.index')
            ->with('status', 'Updated.');
    }

    public function edit(Customer $customer, EnsureCustomerInBranchAction $ensureCustomerInBranchAction): View
    {
        $customer = $ensureCustomerInBranchAction->handle($customer, $this->currentBranchId());

        $statuses = CustomerStatus::cases();

        return view('tenants.customers.edit', [
            'customer' => $customer,
            'statuses' => $statuses,
        ]);
    }

    public function destroy(
        Customer $customer,
        DeleteCustomerAction $action,
        EnsureCustomerInBranchAction $ensureCustomerInBranchAction
    ): RedirectResponse {
        $customer = $ensureCustomerInBranchAction->handle($customer, $this->currentBranchId());

        $action->handle($customer);

        return to_route('tenant.customers.index')
            ->with('status', 'Deleted.');
    }
}

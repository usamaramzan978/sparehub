<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\CustomerVehicle\CreateCustomerVehicleAction;
use App\Actions\Tenant\CustomerVehicle\DeleteCustomerVehicleAction;
use App\Actions\Tenant\CustomerVehicle\EnsureVehicleInBranchAction;
use App\Actions\Tenant\CustomerVehicle\UpdateCustomerVehicleAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CustomerVehicleRequest;
use App\Models\Customer;
use App\Models\CustomerVehicle;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class CustomerVehicleController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $perPage = min(max($request->integer('per_page', 15), 5), 100);
        $search = mb_trim($request->string('search')->toString());
        $allowedSortColumns = ['registration_no', 'model', 'year', 'meter_reading', 'created_at'];
        [$activeSortBy, $activeSortDirection] = $this->resolveSort($request, $allowedSortColumns);

        $vehiclesQuery = CustomerVehicle::query()
            ->with('customer')
            ->whereHas('customer', fn (Builder $query) => $query->where('branch_id', $branchId))
            ->when(filled($search), function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('registration_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('model', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('chassis_no', 'like', sprintf('%%%s%%', $search));
                });
            });

        if ($activeSortBy !== null) {
            $vehiclesQuery->orderBy($activeSortBy, $activeSortDirection);
        } else {
            $vehiclesQuery->latest();
        }

        $vehicles = $vehiclesQuery
            ->paginate($perPage)
            ->withQueryString();

        return view('tenants.customer-vehicles.index', [
            'items' => $vehicles,
            'sortBy' => $activeSortBy,
            'sortDirection' => $activeSortDirection,
        ]);
    }

    public function create(): View
    {
        $customers = Customer::query()
            ->where('branch_id', $this->currentBranchId())
            ->orderBy('name')
            ->get();

        return view('tenants.customer-vehicles.create', [
            'customers' => $customers,
        ]);
    }

    public function store(CustomerVehicleRequest $request, CreateCustomerVehicleAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('tenant.customer-vehicles.index')
            ->with('status', 'Created.');
    }

    public function show(CustomerVehicle $customerVehicle, EnsureVehicleInBranchAction $ensureVehicleInBranchAction): View
    {
        $customerVehicle = $ensureVehicleInBranchAction->handle($customerVehicle, $this->currentBranchId());

        $customerVehicle->load('customer');

        return view('tenants.customer-vehicles.show', [
            'vehicle' => $customerVehicle,
        ]);
    }

    public function edit(CustomerVehicle $customerVehicle, EnsureVehicleInBranchAction $ensureVehicleInBranchAction): View
    {
        $customerVehicle = $ensureVehicleInBranchAction->handle($customerVehicle, $this->currentBranchId());

        $customers = Customer::query()
            ->where('branch_id', $this->currentBranchId())
            ->orderBy('name')
            ->get();

        return view('tenants.customer-vehicles.edit', [
            'vehicle' => $customerVehicle,
            'customers' => $customers,
        ]);
    }

    public function update(
        CustomerVehicleRequest $request,
        CustomerVehicle $customerVehicle,
        UpdateCustomerVehicleAction $action,
        EnsureVehicleInBranchAction $ensureVehicleInBranchAction
    ): RedirectResponse {
        $customerVehicle = $ensureVehicleInBranchAction->handle($customerVehicle, $this->currentBranchId());

        $action->handle($customerVehicle, $request->validated());

        return to_route('tenant.customer-vehicles.index')
            ->with('status', 'Updated.');
    }

    public function destroy(
        CustomerVehicle $customerVehicle,
        DeleteCustomerVehicleAction $action,
        EnsureVehicleInBranchAction $ensureVehicleInBranchAction
    ): RedirectResponse {
        $customerVehicle = $ensureVehicleInBranchAction->handle($customerVehicle, $this->currentBranchId());

        $action->handle($customerVehicle);

        return to_route('tenant.customer-vehicles.index')
            ->with('status', 'Deleted.');
    }
}

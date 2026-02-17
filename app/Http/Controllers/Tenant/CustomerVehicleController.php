<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

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

        $vehicles = CustomerVehicle::query()
            ->with('customer')
            ->whereHas('customer', fn (Builder $query) => $query->where('branch_id', $branchId))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('registration_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('model', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('chassis_no', 'like', sprintf('%%%s%%', $search));
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $customers = Customer::query()
            ->where('branch_id', $branchId)
            ->orderBy('name')
            ->get();

        return view('tenants.customer-vehicles.index', [
            'items' => $vehicles,
            'customers' => $customers,
        ]);
    }

    public function store(CustomerVehicleRequest $request): RedirectResponse
    {
        CustomerVehicle::query()->create($request->validated());

        return to_route('tenant.customer-vehicles.index')
            ->with('status', 'Created.');
    }

    public function show(CustomerVehicle $customerVehicle): View
    {
        $this->ensureVehicleInCurrentBranch($customerVehicle);

        $customerVehicle->load('customer');

        return view('tenants.customer-vehicles.show', [
            'vehicle' => $customerVehicle,
        ]);
    }

    public function update(CustomerVehicleRequest $request, CustomerVehicle $customerVehicle): RedirectResponse
    {
        $this->ensureVehicleInCurrentBranch($customerVehicle);

        $customerVehicle->update($request->validated());

        return to_route('tenant.customer-vehicles.index')
            ->with('status', 'Updated.');
    }

    public function destroy(CustomerVehicle $customerVehicle): RedirectResponse
    {
        $this->ensureVehicleInCurrentBranch($customerVehicle);

        $customerVehicle->delete();

        return to_route('tenant.customer-vehicles.index')
            ->with('status', 'Deleted.');
    }

    private function ensureVehicleInCurrentBranch(CustomerVehicle $customerVehicle): void
    {
        $branchId = $this->currentBranchId();
        $customer = $customerVehicle->customer;

        abort_if(! $customer instanceof Customer || $customer->branch_id !== $branchId, 404);
    }
}

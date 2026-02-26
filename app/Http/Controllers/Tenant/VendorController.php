<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\Vendor\CreateVendorAction;
use App\Actions\Tenant\Vendor\DeleteVendorAction;
use App\Actions\Tenant\Vendor\EnsureVendorInBranchAction;
use App\Actions\Tenant\Vendor\UpdateVendorAction;
use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\VendorRequest;
use App\Models\Vendor;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class VendorController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $perPage = min(max($request->integer('per_page', 15), 5), 100);
        $search = mb_trim($request->string('search')->toString());

        $vendors = Vendor::query()
            ->where('branch_id', $branchId)
            ->when(filled($search), function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('code', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('phone', 'like', sprintf('%%%s%%', $search));
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('tenants.vendors.index', [
            'items' => $vendors,
        ]);
    }

    public function create(): View
    {
        return view('tenants.vendors.create', [
            'statuses' => RecordStatus::cases(),
        ]);
    }

    public function store(VendorRequest $request, CreateVendorAction $action): RedirectResponse
    {
        $action->handle($request->validated(), $this->currentBranchId());

        return to_route('tenant.vendors.index')
            ->with('status', 'Created.');
    }

    public function show(Vendor $vendor, EnsureVendorInBranchAction $ensureVendorInBranchAction): View
    {
        $vendor = $ensureVendorInBranchAction->handle($vendor, $this->currentBranchId());

        $vendor->load('branch');

        return view('tenants.vendors.show', [
            'vendor' => $vendor,
        ]);
    }

    public function edit(Vendor $vendor, EnsureVendorInBranchAction $ensureVendorInBranchAction): View
    {
        $vendor = $ensureVendorInBranchAction->handle($vendor, $this->currentBranchId());

        return view('tenants.vendors.edit', [
            'vendor' => $vendor,
            'statuses' => RecordStatus::cases(),
        ]);
    }

    public function update(
        VendorRequest $request,
        Vendor $vendor,
        UpdateVendorAction $action,
        EnsureVendorInBranchAction $ensureVendorInBranchAction
    ): RedirectResponse {
        $vendor = $ensureVendorInBranchAction->handle($vendor, $this->currentBranchId());
        $action->handle($vendor, $request->validated(), $this->currentBranchId());

        return to_route('tenant.vendors.index')
            ->with('status', 'Updated.');
    }

    public function destroy(
        Vendor $vendor,
        DeleteVendorAction $action,
        EnsureVendorInBranchAction $ensureVendorInBranchAction
    ): RedirectResponse {
        $vendor = $ensureVendorInBranchAction->handle($vendor, $this->currentBranchId());
        $action->handle($vendor);

        return to_route('tenant.vendors.index')
            ->with('status', 'Deleted.');
    }
}

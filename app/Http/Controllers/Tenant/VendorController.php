<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

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
            ->when($search !== '', function (Builder $query) use ($search): void {
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
            'statuses' => RecordStatus::cases(),
        ]);
    }

    public function store(VendorRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $payload['branch_id'] = $this->currentBranchId();

        Vendor::query()->create($payload);

        return to_route('tenant.vendors.index')
            ->with('status', 'Created.');
    }

    public function show(Vendor $vendor): View
    {
        $this->ensureVendorInCurrentBranch($vendor);

        $vendor->load('branch');

        return view('tenants.vendors.show', [
            'vendor' => $vendor,
        ]);
    }

    public function update(VendorRequest $request, Vendor $vendor): RedirectResponse
    {
        $this->ensureVendorInCurrentBranch($vendor);

        $payload = $request->validated();
        $payload['branch_id'] = $this->currentBranchId();

        $vendor->update($payload);

        return to_route('tenant.vendors.index')
            ->with('status', 'Updated.');
    }

    public function destroy(Vendor $vendor): RedirectResponse
    {
        $this->ensureVendorInCurrentBranch($vendor);

        $vendor->delete();

        return to_route('tenant.vendors.index')
            ->with('status', 'Deleted.');
    }

    private function ensureVendorInCurrentBranch(Vendor $vendor): void
    {
        abort_if($vendor->branch_id !== $this->currentBranchId(), 404);
    }
}

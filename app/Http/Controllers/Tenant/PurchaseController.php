<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\PurchaseStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\PurchaseRequest;
use App\Models\Purchase;
use App\Models\Vendor;
use App\Models\Warehouse;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class PurchaseController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $perPage = min(max($request->integer('per_page', 15), 5), 100);
        $search = mb_trim($request->string('search')->toString());

        $items = Purchase::query()
            ->with(['vendor', 'warehouse'])
            ->where('branch_id', $branchId)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('purchase_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('vendor_invoice_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('vendor', fn (Builder $q) => $q->where('name', 'like', sprintf('%%%s%%', $search)));
                });
            })
            ->latest('purchase_date')
            ->paginate($perPage)
            ->withQueryString();

        return view('tenants.purchases.index', [
            'items' => $items,
        ]);
    }

    public function create(): View
    {
        return view('tenants.purchases.create', $this->formOptions());
    }

    public function store(PurchaseRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $payload['branch_id'] = $this->currentBranchId();
        $payload['created_by'] = auth('user')->id();

        Purchase::query()->create($payload);

        return to_route('tenant.purchases.index')->with('status', 'Created.');
    }

    public function show(Purchase $purchase): View
    {
        $this->ensurePurchaseInCurrentBranch($purchase);

        $purchase->load([
            'vendor',
            'warehouse',
            'creator',
            'items.product',
            'items.tax',
            'returns.vendor',
            'payments.vendor',
            'payments.creator',
        ]);

        return view('tenants.purchases.show', [
            'purchase' => $purchase,
        ]);
    }

    public function edit(Purchase $purchase): View
    {
        $this->ensurePurchaseInCurrentBranch($purchase);

        return view('tenants.purchases.edit', array_merge(
            ['purchase' => $purchase],
            $this->formOptions()
        ));
    }

    public function update(PurchaseRequest $request, Purchase $purchase): RedirectResponse
    {
        $this->ensurePurchaseInCurrentBranch($purchase);

        $payload = $request->validated();
        $payload['branch_id'] = $this->currentBranchId();

        $purchase->update($payload);

        return to_route('tenant.purchases.index')->with('status', 'Updated.');
    }

    public function destroy(Purchase $purchase): RedirectResponse
    {
        $this->ensurePurchaseInCurrentBranch($purchase);
        $purchase->delete();

        return to_route('tenant.purchases.index')->with('status', 'Deleted.');
    }

    private function ensurePurchaseInCurrentBranch(Purchase $purchase): void
    {
        abort_if($purchase->branch_id !== $this->currentBranchId(), 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        $branchId = $this->currentBranchId();

        return [
            'vendors' => Vendor::query()->where('branch_id', $branchId)->orderBy('name')->get(),
            'warehouses' => Warehouse::query()->where('branch_id', $branchId)->orderBy('name')->get(),
            'statuses' => PurchaseStatus::cases(),
        ];
    }
}

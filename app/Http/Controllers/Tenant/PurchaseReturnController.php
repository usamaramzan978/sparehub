<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\PurchaseReturnStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\PurchaseReturnRequest;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Vendor;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class PurchaseReturnController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $perPage = min(max($request->integer('per_page', 15), 5), 100);
        $search = mb_trim($request->string('search')->toString());

        $items = PurchaseReturn::query()
            ->with(['vendor', 'purchase'])
            ->where('branch_id', $branchId)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('return_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('vendor', fn (Builder $q) => $q->where('name', 'like', sprintf('%%%s%%', $search)));
                });
            })
            ->latest('return_date')
            ->paginate($perPage)
            ->withQueryString();

        return view('tenants.purchase-returns.index', [
            'items' => $items,
        ]);
    }

    public function create(): View
    {
        return view('tenants.purchase-returns.create', $this->formOptions());
    }

    public function store(PurchaseReturnRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $payload['branch_id'] = $this->currentBranchId();
        $payload['created_by'] = auth('user')->id();

        PurchaseReturn::query()->create($payload);

        return to_route('tenant.purchase-returns.index')->with('status', 'Created.');
    }

    public function show(PurchaseReturn $purchaseReturn): View
    {
        $this->ensurePurchaseReturnInCurrentBranch($purchaseReturn);

        $purchaseReturn->load([
            'vendor',
            'purchase',
            'creator',
            'items.product',
            'items.tax',
            'items.purchaseItem',
        ]);

        return view('tenants.purchase-returns.show', [
            'purchaseReturn' => $purchaseReturn,
        ]);
    }

    public function edit(PurchaseReturn $purchaseReturn): View
    {
        $this->ensurePurchaseReturnInCurrentBranch($purchaseReturn);

        return view('tenants.purchase-returns.edit', array_merge(
            ['purchaseReturn' => $purchaseReturn],
            $this->formOptions()
        ));
    }

    public function update(PurchaseReturnRequest $request, PurchaseReturn $purchaseReturn): RedirectResponse
    {
        $this->ensurePurchaseReturnInCurrentBranch($purchaseReturn);

        $payload = $request->validated();
        $payload['branch_id'] = $this->currentBranchId();

        $purchaseReturn->update($payload);

        return to_route('tenant.purchase-returns.index')->with('status', 'Updated.');
    }

    public function destroy(PurchaseReturn $purchaseReturn): RedirectResponse
    {
        $this->ensurePurchaseReturnInCurrentBranch($purchaseReturn);
        $purchaseReturn->delete();

        return to_route('tenant.purchase-returns.index')->with('status', 'Deleted.');
    }

    private function ensurePurchaseReturnInCurrentBranch(PurchaseReturn $purchaseReturn): void
    {
        abort_if($purchaseReturn->branch_id !== $this->currentBranchId(), 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        $branchId = $this->currentBranchId();

        return [
            'vendors' => Vendor::query()->where('branch_id', $branchId)->orderBy('name')->get(),
            'purchases' => Purchase::query()->where('branch_id', $branchId)->latest('purchase_date')->get(),
            'statuses' => PurchaseReturnStatus::cases(),
        ];
    }
}

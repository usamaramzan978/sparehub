<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\PurchaseReturn\CreatePurchaseReturnAction;
use App\Actions\Tenant\PurchaseReturn\DeletePurchaseReturnAction;
use App\Actions\Tenant\PurchaseReturn\EnsurePurchaseReturnInBranchAction;
use App\Actions\Tenant\PurchaseReturn\UpdatePurchaseReturnAction;
use App\Enums\PurchaseReturnStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\PurchaseReturnRequest;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
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
        $allowedSortColumns = ['return_no', 'return_date', 'status', 'grand_total', 'created_at'];
        [$activeSortBy, $activeSortDirection] = $this->resolveSort($request, $allowedSortColumns);

        $purchaseReturnsQuery = PurchaseReturn::query()
            ->with(['vendor', 'purchase'])
            ->where('branch_id', $branchId)
            ->when(filled($search), function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('return_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('vendor', fn (Builder $q) => $q->where('name', 'like', sprintf('%%%s%%', $search)));
                });
            });

        if ($activeSortBy !== null) {
            $purchaseReturnsQuery->orderBy($activeSortBy, $activeSortDirection);
        } else {
            $purchaseReturnsQuery->latest('return_date');
        }

        $items = $purchaseReturnsQuery
            ->paginate($perPage)
            ->withQueryString();

        return view('tenants.purchase-returns.index', [
            'items' => $items,
            'sortBy' => $activeSortBy,
            'sortDirection' => $activeSortDirection,
        ]);
    }

    public function create(): View
    {
        return view('tenants.purchase-returns.create', $this->formOptions());
    }

    public function store(PurchaseReturnRequest $request, CreatePurchaseReturnAction $action): RedirectResponse
    {
        $action->handle($request->validated(), $this->currentBranchId(), auth('user')->id());

        return to_route('tenant.purchase-returns.index')->with('status', 'Created.');
    }

    public function show(PurchaseReturn $purchaseReturn, EnsurePurchaseReturnInBranchAction $ensurePurchaseReturnInBranchAction): View
    {
        $purchaseReturn = $ensurePurchaseReturnInBranchAction->handle($purchaseReturn, $this->currentBranchId());

        $purchaseReturn->load([
            'vendor',
            'purchase',
            'creator',
            'items.product',
            'items.purchaseItem',
        ]);

        return view('tenants.purchase-returns.show', [
            'purchaseReturn' => $purchaseReturn,
        ]);
    }

    public function edit(PurchaseReturn $purchaseReturn, EnsurePurchaseReturnInBranchAction $ensurePurchaseReturnInBranchAction): View
    {
        $purchaseReturn = $ensurePurchaseReturnInBranchAction->handle($purchaseReturn, $this->currentBranchId());
        $purchaseReturn->load('items');

        return view('tenants.purchase-returns.edit', array_merge(
            ['purchaseReturn' => $purchaseReturn],
            $this->formOptions()
        ));
    }

    public function update(
        PurchaseReturnRequest $request,
        PurchaseReturn $purchaseReturn,
        UpdatePurchaseReturnAction $action,
        EnsurePurchaseReturnInBranchAction $ensurePurchaseReturnInBranchAction
    ): RedirectResponse {
        $purchaseReturn = $ensurePurchaseReturnInBranchAction->handle($purchaseReturn, $this->currentBranchId());
        $action->handle($purchaseReturn, $request->validated(), $this->currentBranchId());

        return to_route('tenant.purchase-returns.index')->with('status', 'Updated.');
    }

    public function destroy(
        PurchaseReturn $purchaseReturn,
        DeletePurchaseReturnAction $action,
        EnsurePurchaseReturnInBranchAction $ensurePurchaseReturnInBranchAction
    ): RedirectResponse {
        $purchaseReturn = $ensurePurchaseReturnInBranchAction->handle($purchaseReturn, $this->currentBranchId());
        $action->handle($purchaseReturn);

        return to_route('tenant.purchase-returns.index')->with('status', 'Deleted.');
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
            'purchaseItems' => PurchaseItem::query()
                ->with(['purchase', 'product'])
                ->where('branch_id', $branchId)
                ->latest()
                ->get(),
            'products' => Product::query()->orderBy('name')->get(),
            'statuses' => PurchaseReturnStatus::cases(),
        ];
    }
}

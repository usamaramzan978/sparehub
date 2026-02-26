<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\PurchaseItem\CreatePurchaseItemAction;
use App\Actions\Tenant\PurchaseItem\DeletePurchaseItemAction;
use App\Actions\Tenant\PurchaseItem\EnsurePurchaseItemInBranchAction;
use App\Actions\Tenant\PurchaseItem\UpdatePurchaseItemAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\PurchaseItemRequest;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Tax;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class PurchaseItemController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $perPage = min(max($request->integer('per_page', 15), 5), 100);
        $search = mb_trim($request->string('search')->toString());
        $allowedSortColumns = ['qty', 'received_qty', 'unit_cost', 'line_total', 'created_at'];
        [$activeSortBy, $activeSortDirection] = $this->resolveSort($request, $allowedSortColumns);

        $purchaseItemsQuery = PurchaseItem::query()
            ->with(['purchase', 'product', 'tax'])
            ->where('branch_id', $branchId)
            ->when(filled($search), function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->whereHas('purchase', fn (Builder $purchaseQuery) => $purchaseQuery->where('purchase_no', 'like', sprintf('%%%s%%', $search)))
                        ->orWhereHas('product', fn (Builder $productQuery) => $productQuery->where('name', 'like', sprintf('%%%s%%', $search)));
                });
            });

        if ($activeSortBy !== null) {
            $purchaseItemsQuery->orderBy($activeSortBy, $activeSortDirection);
        } else {
            $purchaseItemsQuery->latest();
        }

        $items = $purchaseItemsQuery
            ->paginate($perPage)
            ->withQueryString();

        return view('tenants.purchase-items.index', [
            'items' => $items,
            'sortBy' => $activeSortBy,
            'sortDirection' => $activeSortDirection,
        ]);
    }

    public function create(): View
    {
        return view('tenants.purchase-items.create', $this->formOptions());
    }

    public function store(PurchaseItemRequest $request, CreatePurchaseItemAction $action): RedirectResponse
    {
        $action->handle($request->validated(), $this->currentBranchId());

        return to_route('tenant.purchase-items.index')->with('status', 'Created.');
    }

    public function show(PurchaseItem $purchaseItem, EnsurePurchaseItemInBranchAction $ensurePurchaseItemInBranchAction): View
    {
        $purchaseItem = $ensurePurchaseItemInBranchAction->handle($purchaseItem, $this->currentBranchId());

        $purchaseItem->load(['purchase.vendor', 'product', 'tax']);

        return view('tenants.purchase-items.show', [
            'purchaseItem' => $purchaseItem,
        ]);
    }

    public function edit(PurchaseItem $purchaseItem, EnsurePurchaseItemInBranchAction $ensurePurchaseItemInBranchAction): View
    {
        $purchaseItem = $ensurePurchaseItemInBranchAction->handle($purchaseItem, $this->currentBranchId());

        return view('tenants.purchase-items.edit', array_merge(
            ['purchaseItem' => $purchaseItem],
            $this->formOptions()
        ));
    }

    public function update(
        PurchaseItemRequest $request,
        PurchaseItem $purchaseItem,
        UpdatePurchaseItemAction $action,
        EnsurePurchaseItemInBranchAction $ensurePurchaseItemInBranchAction
    ): RedirectResponse {
        $purchaseItem = $ensurePurchaseItemInBranchAction->handle($purchaseItem, $this->currentBranchId());
        $action->handle($purchaseItem, $request->validated(), $this->currentBranchId());

        return to_route('tenant.purchase-items.index')->with('status', 'Updated.');
    }

    public function destroy(
        PurchaseItem $purchaseItem,
        DeletePurchaseItemAction $action,
        EnsurePurchaseItemInBranchAction $ensurePurchaseItemInBranchAction
    ): RedirectResponse {
        $purchaseItem = $ensurePurchaseItemInBranchAction->handle($purchaseItem, $this->currentBranchId());
        $action->handle($purchaseItem);

        return to_route('tenant.purchase-items.index')->with('status', 'Deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        $branchId = $this->currentBranchId();

        return [
            'purchases' => Purchase::query()->where('branch_id', $branchId)->latest('purchase_date')->get(),
            'products' => Product::query()->orderBy('name')->get(),
            'taxes' => Tax::query()->orderBy('name')->get(),
        ];
    }
}

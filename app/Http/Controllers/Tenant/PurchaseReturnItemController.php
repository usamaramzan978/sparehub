<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\PurchaseReturnItem\CreatePurchaseReturnItemAction;
use App\Actions\Tenant\PurchaseReturnItem\DeletePurchaseReturnItemAction;
use App\Actions\Tenant\PurchaseReturnItem\EnsurePurchaseReturnItemInBranchAction;
use App\Actions\Tenant\PurchaseReturnItem\UpdatePurchaseReturnItemAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\PurchaseReturnItemRequest;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Tax;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class PurchaseReturnItemController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $perPage = min(max($request->integer('per_page', 15), 5), 100);
        $search = mb_trim($request->string('search')->toString());

        $items = PurchaseReturnItem::query()
            ->with(['purchaseReturn', 'purchaseItem', 'product', 'tax'])
            ->whereHas('purchaseReturn', fn ($query) => $query->where('branch_id', $branchId))
            ->when(filled($search), function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->whereHas('purchaseReturn', fn (Builder $purchaseReturnQuery) => $purchaseReturnQuery->where('return_no', 'like', sprintf('%%%s%%', $search)))
                        ->orWhereHas('product', fn (Builder $productQuery) => $productQuery->where('name', 'like', sprintf('%%%s%%', $search)));
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('tenants.purchase-return-items.index', [
            'items' => $items,
        ]);
    }

    public function create(): View
    {
        return view('tenants.purchase-return-items.create', $this->formOptions());
    }

    public function store(PurchaseReturnItemRequest $request, CreatePurchaseReturnItemAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('tenant.purchase-return-items.index')->with('status', 'Created.');
    }

    public function show(
        PurchaseReturnItem $purchaseReturnItem,
        EnsurePurchaseReturnItemInBranchAction $ensurePurchaseReturnItemInBranchAction
    ): View {
        $purchaseReturnItem = $ensurePurchaseReturnItemInBranchAction->handle($purchaseReturnItem, $this->currentBranchId());

        $purchaseReturnItem->load(['purchaseReturn.vendor', 'purchaseItem', 'product', 'tax']);

        return view('tenants.purchase-return-items.show', [
            'purchaseReturnItem' => $purchaseReturnItem,
        ]);
    }

    public function edit(
        PurchaseReturnItem $purchaseReturnItem,
        EnsurePurchaseReturnItemInBranchAction $ensurePurchaseReturnItemInBranchAction
    ): View {
        $purchaseReturnItem = $ensurePurchaseReturnItemInBranchAction->handle($purchaseReturnItem, $this->currentBranchId());

        return view('tenants.purchase-return-items.edit', array_merge(
            ['purchaseReturnItem' => $purchaseReturnItem],
            $this->formOptions()
        ));
    }

    public function update(
        PurchaseReturnItemRequest $request,
        PurchaseReturnItem $purchaseReturnItem,
        UpdatePurchaseReturnItemAction $action,
        EnsurePurchaseReturnItemInBranchAction $ensurePurchaseReturnItemInBranchAction
    ): RedirectResponse {
        $purchaseReturnItem = $ensurePurchaseReturnItemInBranchAction->handle($purchaseReturnItem, $this->currentBranchId());
        $action->handle($purchaseReturnItem, $request->validated());

        return to_route('tenant.purchase-return-items.index')->with('status', 'Updated.');
    }

    public function destroy(
        PurchaseReturnItem $purchaseReturnItem,
        DeletePurchaseReturnItemAction $action,
        EnsurePurchaseReturnItemInBranchAction $ensurePurchaseReturnItemInBranchAction
    ): RedirectResponse {
        $purchaseReturnItem = $ensurePurchaseReturnItemInBranchAction->handle($purchaseReturnItem, $this->currentBranchId());
        $action->handle($purchaseReturnItem);

        return to_route('tenant.purchase-return-items.index')->with('status', 'Deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        $branchId = $this->currentBranchId();

        return [
            'purchaseReturns' => PurchaseReturn::query()->where('branch_id', $branchId)->latest('return_date')->get(),
            'purchaseItems' => PurchaseItem::query()
                ->with(['purchase', 'product'])
                ->where('branch_id', $branchId)
                ->latest()
                ->get(),
            'products' => Product::query()->orderBy('name')->get(),
            'taxes' => Tax::query()->orderBy('name')->get(),
        ];
    }
}

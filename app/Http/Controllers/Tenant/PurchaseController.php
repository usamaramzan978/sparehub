<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\Purchase\CreatePurchaseAction;
use App\Actions\Tenant\Purchase\DeletePurchaseAction;
use App\Actions\Tenant\Purchase\EnsurePurchaseInBranchAction;
use App\Actions\Tenant\Purchase\UpdatePurchaseAction;
use App\Enums\PurchaseStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\PurchaseRequest;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Vendor;
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
        $allowedSortColumns = ['purchase_no', 'purchase_date', 'status', 'grand_total', 'created_at'];
        [$activeSortBy, $activeSortDirection] = $this->resolveSort($request, $allowedSortColumns);

        $purchasesQuery = Purchase::query()
            ->with(['vendor'])
            ->where('branch_id', $branchId)
            ->when(filled($search), function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('purchase_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('vendor', fn (Builder $q) => $q->where('name', 'like', sprintf('%%%s%%', $search)));
                });
            });

        if ($activeSortBy !== null) {
            $purchasesQuery->orderBy($activeSortBy, $activeSortDirection);
        } else {
            $purchasesQuery->latest('purchase_date');
        }

        $items = $purchasesQuery
            ->paginate($perPage)
            ->withQueryString();

        return view('tenants.purchases.index', [
            'items' => $items,
            'sortBy' => $activeSortBy,
            'sortDirection' => $activeSortDirection,
        ]);
    }

    public function create(): View
    {
        return view('tenants.purchases.create', $this->formOptions());
    }

    public function store(PurchaseRequest $request, CreatePurchaseAction $action): RedirectResponse
    {
        $action->handle($request->validated(), $this->currentBranchId(), auth('user')->id());

        return to_route('tenant.purchases.index')->with('status', 'Created.');
    }

    public function show(Purchase $purchase, EnsurePurchaseInBranchAction $ensurePurchaseInBranchAction): View
    {
        $purchase = $ensurePurchaseInBranchAction->handle($purchase, $this->currentBranchId());

        $purchase->load([
            'vendor',
            'creator',
            'items.product',
            'returns.vendor',
            'payments.vendor',
            'payments.creator',
        ]);

        return view('tenants.purchases.show', [
            'purchase' => $purchase,
        ]);
    }

    public function edit(Purchase $purchase, EnsurePurchaseInBranchAction $ensurePurchaseInBranchAction): View
    {
        $purchase = $ensurePurchaseInBranchAction->handle($purchase, $this->currentBranchId());
        $purchase->load('items');

        return view('tenants.purchases.edit', array_merge(
            ['purchase' => $purchase],
            $this->formOptions()
        ));
    }

    public function update(
        PurchaseRequest $request,
        Purchase $purchase,
        UpdatePurchaseAction $action,
        EnsurePurchaseInBranchAction $ensurePurchaseInBranchAction
    ): RedirectResponse {
        $purchase = $ensurePurchaseInBranchAction->handle($purchase, $this->currentBranchId());
        $action->handle($purchase, $request->validated(), $this->currentBranchId());

        return to_route('tenant.purchases.index')->with('status', 'Updated.');
    }

    public function destroy(
        Purchase $purchase,
        DeletePurchaseAction $action,
        EnsurePurchaseInBranchAction $ensurePurchaseInBranchAction
    ): RedirectResponse {
        $purchase = $ensurePurchaseInBranchAction->handle($purchase, $this->currentBranchId());
        $action->handle($purchase);

        return to_route('tenant.purchases.index')->with('status', 'Deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        $branchId = $this->currentBranchId();

        return [
            'vendors' => Vendor::query()->where('branch_id', $branchId)->orderBy('name')->get(),
            'products' => Product::query()->orderBy('name')->get(),
            'statuses' => PurchaseStatus::cases(),
        ];
    }
}

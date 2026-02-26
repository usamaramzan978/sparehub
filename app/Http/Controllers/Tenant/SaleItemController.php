<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\SaleItem\CreateSaleItemAction;
use App\Actions\Tenant\SaleItem\DeleteSaleItemAction;
use App\Actions\Tenant\SaleItem\EnsureSaleItemInBranchAction;
use App\Actions\Tenant\SaleItem\UpdateSaleItemAction;
use App\Enums\SaleLineType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\SaleItemRequest;
use App\Models\JobCardService;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\ServiceCatalog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class SaleItemController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $perPage = min(max($request->integer('per_page', 15), 5), 100);
        $search = mb_trim($request->string('search')->toString());
        $allowedSortColumns = ['line_type', 'qty', 'unit_price', 'mechanic_charge', 'line_total', 'created_at'];
        [$activeSortBy, $activeSortDirection] = $this->resolveSort($request, $allowedSortColumns);

        $itemsQuery = SaleItem::query()
            ->with(['sale', 'product', 'serviceCatalog', 'jobCardService', 'mechanic'])
            ->where('branch_id', $branchId)
            ->when(filled($search), function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->whereHas('sale', fn (Builder $saleQuery) => $saleQuery->where('invoice_no', 'like', sprintf('%%%s%%', $search)))
                        ->orWhere('description', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('product', fn (Builder $productQuery) => $productQuery->where('name', 'like', sprintf('%%%s%%', $search)))
                        ->orWhereHas('serviceCatalog', fn (Builder $serviceQuery) => $serviceQuery->where('name', 'like', sprintf('%%%s%%', $search)))
                        ->orWhereHas('mechanic', fn (Builder $mechanicQuery) => $mechanicQuery->where('name', 'like', sprintf('%%%s%%', $search)));
                });
            });

        if ($activeSortBy !== null) {
            $itemsQuery->orderBy($activeSortBy, $activeSortDirection);
        } else {
            $itemsQuery->latest();
        }

        $items = $itemsQuery
            ->paginate($perPage)
            ->withQueryString();

        return view('tenants.sale-items.index', [
            'items' => $items,
            'sortBy' => $activeSortBy,
            'sortDirection' => $activeSortDirection,
        ]);
    }

    public function create(): View
    {
        return view('tenants.sale-items.create', $this->formOptions());
    }

    public function store(SaleItemRequest $request, CreateSaleItemAction $action): RedirectResponse
    {
        $action->handle($request->validated(), $this->currentBranchId());

        return to_route('tenant.sale-items.index')->with('status', 'Created.');
    }

    public function show(SaleItem $saleItem, EnsureSaleItemInBranchAction $ensureSaleItemInBranchAction): View
    {
        $saleItem = $ensureSaleItemInBranchAction->handle($saleItem, $this->currentBranchId());

        $saleItem->load(['sale.customer', 'product', 'serviceCatalog', 'jobCardService']);
        $saleItem->load('mechanic');

        return view('tenants.sale-items.show', [
            'saleItem' => $saleItem,
        ]);
    }

    public function edit(SaleItem $saleItem, EnsureSaleItemInBranchAction $ensureSaleItemInBranchAction): View
    {
        $saleItem = $ensureSaleItemInBranchAction->handle($saleItem, $this->currentBranchId());

        return view('tenants.sale-items.edit', array_merge(
            ['saleItem' => $saleItem],
            $this->formOptions()
        ));
    }

    public function update(
        SaleItemRequest $request,
        SaleItem $saleItem,
        UpdateSaleItemAction $action,
        EnsureSaleItemInBranchAction $ensureSaleItemInBranchAction
    ): RedirectResponse {
        $saleItem = $ensureSaleItemInBranchAction->handle($saleItem, $this->currentBranchId());
        $action->handle($saleItem, $request->validated(), $this->currentBranchId());

        return to_route('tenant.sale-items.index')->with('status', 'Updated.');
    }

    public function destroy(
        SaleItem $saleItem,
        DeleteSaleItemAction $action,
        EnsureSaleItemInBranchAction $ensureSaleItemInBranchAction
    ): RedirectResponse {
        $saleItem = $ensureSaleItemInBranchAction->handle($saleItem, $this->currentBranchId());
        $action->handle($saleItem);

        return to_route('tenant.sale-items.index')->with('status', 'Deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        $branchId = $this->currentBranchId();

        return [
            'sales' => Sale::query()->where('branch_id', $branchId)->latest('invoice_date')->get(),
            'products' => Product::query()->orderBy('name')->get(),
            'serviceCatalogs' => ServiceCatalog::query()->where('branch_id', $branchId)->orderBy('name')->get(),
            'jobCardServices' => JobCardService::query()->whereHas('jobCard', fn ($q) => $q->where('branch_id', $branchId))->get(),
            'mechanics' => User::query()->where('branch_id', $branchId)->active()->orderBy('name')->get(['id', 'name']),
            'lineTypes' => SaleLineType::cases(),
        ];
    }
}

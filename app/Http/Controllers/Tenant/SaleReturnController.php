<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\SaleReturn\CreateSaleReturnAction;
use App\Actions\Tenant\SaleReturn\DeleteSaleReturnAction;
use App\Actions\Tenant\SaleReturn\EnsureSaleReturnInBranchAction;
use App\Actions\Tenant\SaleReturn\UpdateSaleReturnAction;
use App\Enums\SaleLineType;
use App\Enums\SaleReturnStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\SaleReturnRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\Tax;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class SaleReturnController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $perPage = min(max($request->integer('per_page', 15), 5), 100);
        $search = mb_trim($request->string('search')->toString());
        $allowedSortColumns = ['return_no', 'return_date', 'status', 'grand_total', 'created_at'];
        [$activeSortBy, $activeSortDirection] = $this->resolveSort($request, $allowedSortColumns);

        $saleReturnsQuery = SaleReturn::query()
            ->with(['customer', 'sale'])
            ->where('branch_id', $branchId)
            ->when(filled($search), function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('return_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('customer', fn (Builder $q) => $q->where('name', 'like', sprintf('%%%s%%', $search)));
                });
            });

        if ($activeSortBy !== null) {
            $saleReturnsQuery->orderBy($activeSortBy, $activeSortDirection);
        } else {
            $saleReturnsQuery->latest('return_date');
        }

        $items = $saleReturnsQuery
            ->paginate($perPage)
            ->withQueryString();

        return view('tenants.sale-returns.index', [
            'items' => $items,
            'sortBy' => $activeSortBy,
            'sortDirection' => $activeSortDirection,
        ]);
    }

    public function create(): View
    {
        return view('tenants.sale-returns.create', $this->formOptions());
    }

    public function invoiceItems(string $sale): JsonResponse
    {
        $branchId = $this->currentBranchId();
        $saleModel = Sale::query()
            ->withoutGlobalScopes()
            ->whereKey($sale)
            ->first();

        if (! $saleModel instanceof Sale || $saleModel->branch_id !== $branchId) {
            return response()->json([
                'customer_id' => null,
                'items' => [],
            ]);
        }

        $saleItems = $saleModel->items()
            ->withoutGlobalScopes()
            ->with('product')
            ->where('line_type', SaleLineType::PRODUCT->value)
            ->get();

        $returnedQtyBySaleItem = SaleReturnItem::query()
            ->selectRaw('sale_item_id, SUM(qty) as returned_qty')
            ->whereIn('sale_item_id', $saleItems->pluck('id'))
            ->groupBy('sale_item_id')
            ->pluck('returned_qty', 'sale_item_id');

        $items = $saleItems->map(fn (SaleItem $saleItem): array => [
            'sale_item_id' => $saleItem->id,
            'product_id' => $saleItem->product_id,
            'product_name' => ($saleItem->product?->name ?? '-') . ($saleItem->product?->sku ? ' (' . $saleItem->product->sku . ')' : ''),
            'sold_qty' => (float) $saleItem->qty,
            'returned_qty' => (float) ($returnedQtyBySaleItem[$saleItem->id] ?? 0),
            'available_qty' => (float) max($saleItem->qty - ($returnedQtyBySaleItem[$saleItem->id] ?? 0), 0),
            'unit_price' => (float) $saleItem->unit_price,
            'tax_amount' => (float) ($saleItem->qty > 0 ? ($saleItem->tax_amount / $saleItem->qty) : 0),
        ])->values();

        return response()->json([
            'customer_id' => $saleModel->customer_id,
            'items' => $items,
        ]);
    }

    public function store(SaleReturnRequest $request, CreateSaleReturnAction $action): RedirectResponse
    {
        $action->handle($request->validated(), $this->currentBranchId(), auth('user')->id());

        return to_route('tenant.sale-returns.index')->with('status', 'Created.');
    }

    public function show(SaleReturn $saleReturn, EnsureSaleReturnInBranchAction $ensureSaleReturnInBranchAction): View
    {
        $saleReturn = $ensureSaleReturnInBranchAction->handle($saleReturn, $this->currentBranchId());

        $saleReturn->load([
            'customer',
            'sale',
            'creator',
            'items.product',
            'items.tax',
            'items.saleItem',
        ]);

        return view('tenants.sale-returns.show', [
            'saleReturn' => $saleReturn,
        ]);
    }

    public function edit(SaleReturn $saleReturn, EnsureSaleReturnInBranchAction $ensureSaleReturnInBranchAction): View
    {
        $saleReturn = $ensureSaleReturnInBranchAction->handle($saleReturn, $this->currentBranchId());
        $saleReturn->load(['items.saleItem.sale', 'items.product']);

        return view('tenants.sale-returns.edit', array_merge(
            ['saleReturn' => $saleReturn],
            $this->formOptions()
        ));
    }

    public function update(
        SaleReturnRequest $request,
        SaleReturn $saleReturn,
        UpdateSaleReturnAction $action,
        EnsureSaleReturnInBranchAction $ensureSaleReturnInBranchAction
    ): RedirectResponse {
        $saleReturn = $ensureSaleReturnInBranchAction->handle($saleReturn, $this->currentBranchId());
        $action->handle($saleReturn, $request->validated(), $this->currentBranchId());

        return to_route('tenant.sale-returns.index')->with('status', 'Updated.');
    }

    public function destroy(
        SaleReturn $saleReturn,
        DeleteSaleReturnAction $action,
        EnsureSaleReturnInBranchAction $ensureSaleReturnInBranchAction
    ): RedirectResponse {
        $saleReturn = $ensureSaleReturnInBranchAction->handle($saleReturn, $this->currentBranchId());
        $action->handle($saleReturn);

        return to_route('tenant.sale-returns.index')->with('status', 'Deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        $branchId = $this->currentBranchId();

        $sales = Sale::query()->where('branch_id', $branchId)->latest('invoice_date')->get();

        return [
            'customers' => Customer::query()->where('branch_id', $branchId)->orderBy('name')->get(),
            'sales' => $sales,
            'products' => Product::query()->orderBy('name')->get(),
            'taxes' => Tax::query()->orderBy('name')->get(),
            'statuses' => SaleReturnStatus::cases(),
        ];
    }
}

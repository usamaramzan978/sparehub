<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\SaleLineType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\SaleItemRequest;
use App\Models\InventoryStock;
use App\Models\JobCardService;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\ServiceCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class SaleItemController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $perPage = min(max($request->integer('per_page', 15), 5), 100);

        $items = SaleItem::query()
            ->with(['sale', 'product', 'serviceCatalog', 'jobCardService'])
            ->where('branch_id', $branchId)
            ->latest()
            ->paginate($perPage);

        return view('tenants.sale-items.index', [
            'items' => $items,
        ]);
    }

    public function create(): View
    {
        return view('tenants.sale-items.create', $this->formOptions());
    }

    public function store(SaleItemRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $payload['branch_id'] = $this->currentBranchId();
        $payload['line_total'] = ((float) $payload['qty'] * (float) $payload['unit_price']) - (float) ($payload['discount_amount'] ?? 0) + (float) ($payload['tax_amount'] ?? 0);

        $saleItem = SaleItem::query()->create($payload);
        $this->syncStockForSaleItems(collect([$saleItem]), $payload['branch_id']);
        $this->recalculateSaleTotals($saleItem->sale);

        return to_route('tenant.sale-items.index')->with('status', 'Created.');
    }

    public function show(SaleItem $saleItem): View
    {
        $this->ensureSaleItemInCurrentBranch($saleItem);

        $saleItem->load(['sale.customer', 'product', 'serviceCatalog', 'jobCardService']);

        return view('tenants.sale-items.show', [
            'saleItem' => $saleItem,
        ]);
    }

    public function edit(SaleItem $saleItem): View
    {
        $this->ensureSaleItemInCurrentBranch($saleItem);

        return view('tenants.sale-items.edit', array_merge(
            ['saleItem' => $saleItem],
            $this->formOptions()
        ));
    }

    public function update(SaleItemRequest $request, SaleItem $saleItem): RedirectResponse
    {
        $this->ensureSaleItemInCurrentBranch($saleItem);

        $payload = $request->validated();
        $payload['branch_id'] = $this->currentBranchId();
        $payload['line_total'] = ((float) $payload['qty'] * (float) $payload['unit_price']) - (float) ($payload['discount_amount'] ?? 0) + (float) ($payload['tax_amount'] ?? 0);

        $originalItem = clone $saleItem;
        $this->syncStockForSaleItems(collect([$originalItem]), $payload['branch_id'], reverse: true);
        $saleItem->update($payload);
        $this->syncStockForSaleItems(collect([$saleItem]), $payload['branch_id']);
        $this->recalculateSaleTotals($saleItem->sale);

        return to_route('tenant.sale-items.index')->with('status', 'Updated.');
    }

    public function destroy(SaleItem $saleItem): RedirectResponse
    {
        $this->ensureSaleItemInCurrentBranch($saleItem);
        $sale = $saleItem->sale;
        $this->syncStockForSaleItems(collect([$saleItem]), $saleItem->branch_id, reverse: true);
        $saleItem->delete();
        $this->recalculateSaleTotals($sale);

        return to_route('tenant.sale-items.index')->with('status', 'Deleted.');
    }

    private function ensureSaleItemInCurrentBranch(SaleItem $saleItem): void
    {
        abort_if($saleItem->branch_id !== $this->currentBranchId(), 404);
    }

    private function recalculateSaleTotals(?Sale $sale): void
    {
        if (! $sale instanceof Sale) {
            return;
        }

        $items = $sale->items()->get();
        $subTotal = (float) $items->sum(fn (SaleItem $item): float => (float) $item->qty * (float) $item->unit_price);
        $discountTotal = (float) $items->sum(fn (SaleItem $item): float => (float) $item->discount_amount);
        $taxTotal = (float) $items->sum(fn (SaleItem $item): float => (float) $item->tax_amount);
        $grandTotal = (float) $items->sum(fn (SaleItem $item): float => (float) $item->line_total);
        $paidTotal = (float) $sale->payments()->sum('amount');

        $sale->update([
            'sub_total' => $subTotal,
            'discount_total' => $discountTotal,
            'tax_total' => $taxTotal,
            'grand_total' => $grandTotal,
            'paid_total' => $paidTotal,
            'balance_due' => $grandTotal - $paidTotal,
        ]);
    }

    /**
     * @param  Collection<int, SaleItem>  $saleItems
     */
    private function syncStockForSaleItems(Collection $saleItems, string $branchId, bool $reverse = false): void
    {
        $qtyByProduct = $saleItems
            ->filter(fn (SaleItem $item): bool => $item->product_id !== null)
            ->groupBy('product_id')
            ->map(fn (Collection $items): float => (float) $items->sum('qty'));

        if ($qtyByProduct->isEmpty()) {
            return;
        }

        $trackedProductIds = Product::query()
            ->whereIn('id', $qtyByProduct->keys()->all())
            ->where('track_stock', true)
            ->pluck('id')
            ->all();

        if ($trackedProductIds === []) {
            return;
        }

        foreach ($trackedProductIds as $productId) {
            $qty = (float) ($qtyByProduct->get($productId) ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $stockRow = InventoryStock::query()
                ->where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->oldest('updated_at')
                ->first();

            if (! $stockRow instanceof InventoryStock) {
                continue;
            }

            $adjustedQty = $reverse
                ? (float) $stockRow->qty_on_hand + $qty
                : (float) $stockRow->qty_on_hand - $qty;

            $stockRow->update([
                'qty_on_hand' => $adjustedQty,
            ]);
        }
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
            'lineTypes' => SaleLineType::cases(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\StockMoveType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\PurchaseReturnItemRequest;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\StockMove;
use App\Models\Tax;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class PurchaseReturnItemController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $perPage = min(max($request->integer('per_page', 15), 5), 100);

        $items = PurchaseReturnItem::query()
            ->with(['purchaseReturn', 'purchaseItem', 'product', 'tax'])
            ->whereHas('purchaseReturn', fn ($query) => $query->where('branch_id', $branchId))
            ->latest()
            ->paginate($perPage);

        return view('tenants.purchase-return-items.index', [
            'items' => $items,
        ]);
    }

    public function create(): View
    {
        return view('tenants.purchase-return-items.create', $this->formOptions());
    }

    public function store(PurchaseReturnItemRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $payload['line_total'] = ((float) $payload['qty'] * (float) $payload['unit_cost']) + (float) ($payload['tax_amount'] ?? 0);

        $item = PurchaseReturnItem::query()->create($payload);
        $purchaseReturn = $item->purchaseReturn;
        abort_if(! $purchaseReturn instanceof PurchaseReturn, 404);

        $this->syncStockForPurchaseReturnItems(collect([$item]), $purchaseReturn->branch_id);
        $this->recalculatePurchaseReturnTotals($purchaseReturn);

        return to_route('tenant.purchase-return-items.index')->with('status', 'Created.');
    }

    public function show(PurchaseReturnItem $purchaseReturnItem): View
    {
        $this->ensurePurchaseReturnItemInCurrentBranch($purchaseReturnItem);

        $purchaseReturnItem->load(['purchaseReturn.vendor', 'purchaseItem', 'product', 'tax']);

        return view('tenants.purchase-return-items.show', [
            'purchaseReturnItem' => $purchaseReturnItem,
        ]);
    }

    public function edit(PurchaseReturnItem $purchaseReturnItem): View
    {
        $this->ensurePurchaseReturnItemInCurrentBranch($purchaseReturnItem);

        return view('tenants.purchase-return-items.edit', array_merge(
            ['purchaseReturnItem' => $purchaseReturnItem],
            $this->formOptions()
        ));
    }

    public function update(PurchaseReturnItemRequest $request, PurchaseReturnItem $purchaseReturnItem): RedirectResponse
    {
        $this->ensurePurchaseReturnItemInCurrentBranch($purchaseReturnItem);

        $payload = $request->validated();
        $payload['line_total'] = ((float) $payload['qty'] * (float) $payload['unit_cost']) + (float) ($payload['tax_amount'] ?? 0);

        $purchaseReturn = $purchaseReturnItem->purchaseReturn;
        abort_if(! $purchaseReturn instanceof PurchaseReturn, 404);

        $this->syncStockForPurchaseReturnItems(collect([$purchaseReturnItem]), $purchaseReturn->branch_id, reverse: true);
        $purchaseReturnItem->update($payload);
        $this->syncStockForPurchaseReturnItems(collect([$purchaseReturnItem]), $purchaseReturn->branch_id);
        $this->recalculatePurchaseReturnTotals($purchaseReturn);

        return to_route('tenant.purchase-return-items.index')->with('status', 'Updated.');
    }

    public function destroy(PurchaseReturnItem $purchaseReturnItem): RedirectResponse
    {
        $this->ensurePurchaseReturnItemInCurrentBranch($purchaseReturnItem);

        $purchaseReturn = $purchaseReturnItem->purchaseReturn;
        abort_if(! $purchaseReturn instanceof PurchaseReturn, 404);

        $this->syncStockForPurchaseReturnItems(collect([$purchaseReturnItem]), $purchaseReturn->branch_id, reverse: true);
        $purchaseReturnItem->delete();
        $this->recalculatePurchaseReturnTotals($purchaseReturn);

        return to_route('tenant.purchase-return-items.index')->with('status', 'Deleted.');
    }

    private function ensurePurchaseReturnItemInCurrentBranch(PurchaseReturnItem $purchaseReturnItem): void
    {
        $purchaseReturn = $purchaseReturnItem->purchaseReturn;
        abort_if(! $purchaseReturn instanceof PurchaseReturn || $purchaseReturn->branch_id !== $this->currentBranchId(), 404);
    }

    private function recalculatePurchaseReturnTotals(?PurchaseReturn $purchaseReturn): void
    {
        if (! $purchaseReturn instanceof PurchaseReturn) {
            return;
        }

        $items = $purchaseReturn->items()->get();
        $subTotal = (float) $items->sum(fn (PurchaseReturnItem $item): float => (float) $item->qty * (float) $item->unit_cost);
        $taxTotal = (float) $items->sum(fn (PurchaseReturnItem $item): float => (float) $item->tax_amount);
        $grandTotal = (float) $items->sum(fn (PurchaseReturnItem $item): float => (float) $item->line_total);

        $purchaseReturn->update([
            'sub_total' => $subTotal,
            'tax_total' => $taxTotal,
            'grand_total' => $grandTotal,
        ]);
    }

    /**
     * @param  Collection<int, PurchaseReturnItem>  $purchaseReturnItems
     */
    private function syncStockForPurchaseReturnItems(Collection $purchaseReturnItems, string $branchId, bool $reverse = false): void
    {
        $qtyByProduct = $purchaseReturnItems
            ->filter(fn (PurchaseReturnItem $item): bool => $item->product_id !== null)
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
                ->first();

            if (! $stockRow instanceof InventoryStock) {
                if (! $reverse) {
                    continue;
                }

                $stockRow = InventoryStock::query()->create([
                    'branch_id' => $branchId,
                    'product_id' => $productId,
                    'qty_on_hand' => 0,
                    'qty_reserved' => 0,
                    'avg_cost' => 0,
                ]);
            }

            $adjustedQty = $reverse
                ? (float) $stockRow->qty_on_hand + $qty
                : (float) $stockRow->qty_on_hand - $qty;

            $stockRow->update([
                'qty_on_hand' => $adjustedQty,
            ]);

            StockMove::query()->create([
                'product_id' => $productId,
                'branch_id' => $branchId,
                'warehouse_id' => null,
                'created_by' => auth('user')->id(),
                'move_type' => $reverse ? StockMoveType::PURCHASE->value : StockMoveType::PURCHASE_RETURN->value,
                'qty' => $qty,
                'unit_cost' => 0,
                'total_cost' => 0,
                'reference_type' => null,
                'reference_id' => null,
                'remarks' => $reverse ? 'Stock restored from purchase return item change/delete.' : 'Stock reduced by purchase return item.',
                'occurred_at' => now(),
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

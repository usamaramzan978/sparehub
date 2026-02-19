<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\StockMoveType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\PurchaseItemRequest;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMove;
use App\Models\Tax;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class PurchaseItemController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $perPage = min(max($request->integer('per_page', 15), 5), 100);

        $items = PurchaseItem::query()
            ->with(['purchase', 'product', 'tax'])
            ->where('branch_id', $branchId)
            ->latest()
            ->paginate($perPage);

        return view('tenants.purchase-items.index', [
            'items' => $items,
        ]);
    }

    public function create(): View
    {
        return view('tenants.purchase-items.create', $this->formOptions());
    }

    public function store(PurchaseItemRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $payload['branch_id'] = $this->currentBranchId();
        $payload['received_qty'] ??= 0;
        $payload['line_total'] = ((float) $payload['qty'] * (float) $payload['unit_cost']) - (float) ($payload['discount_amount'] ?? 0) + (float) ($payload['tax_amount'] ?? 0);

        $item = PurchaseItem::query()->create($payload);
        $this->syncStockForPurchaseItems(collect([$item]), $payload['branch_id']);
        $this->recalculatePurchaseTotals($item->purchase);

        return to_route('tenant.purchase-items.index')->with('status', 'Created.');
    }

    public function show(PurchaseItem $purchaseItem): View
    {
        $this->ensurePurchaseItemInCurrentBranch($purchaseItem);

        $purchaseItem->load(['purchase.vendor', 'product', 'tax']);

        return view('tenants.purchase-items.show', [
            'purchaseItem' => $purchaseItem,
        ]);
    }

    public function edit(PurchaseItem $purchaseItem): View
    {
        $this->ensurePurchaseItemInCurrentBranch($purchaseItem);

        return view('tenants.purchase-items.edit', array_merge(
            ['purchaseItem' => $purchaseItem],
            $this->formOptions()
        ));
    }

    public function update(PurchaseItemRequest $request, PurchaseItem $purchaseItem): RedirectResponse
    {
        $this->ensurePurchaseItemInCurrentBranch($purchaseItem);

        $payload = $request->validated();
        $payload['branch_id'] = $this->currentBranchId();
        $payload['received_qty'] ??= 0;
        $payload['line_total'] = ((float) $payload['qty'] * (float) $payload['unit_cost']) - (float) ($payload['discount_amount'] ?? 0) + (float) ($payload['tax_amount'] ?? 0);

        $this->syncStockForPurchaseItems(collect([$purchaseItem]), $purchaseItem->branch_id, reverse: true);
        $purchaseItem->update($payload);
        $this->syncStockForPurchaseItems(collect([$purchaseItem]), $purchaseItem->branch_id);
        $this->recalculatePurchaseTotals($purchaseItem->purchase);

        return to_route('tenant.purchase-items.index')->with('status', 'Updated.');
    }

    public function destroy(PurchaseItem $purchaseItem): RedirectResponse
    {
        $this->ensurePurchaseItemInCurrentBranch($purchaseItem);
        $purchase = $purchaseItem->purchase;
        $this->syncStockForPurchaseItems(collect([$purchaseItem]), $purchaseItem->branch_id, reverse: true);
        $purchaseItem->delete();
        $this->recalculatePurchaseTotals($purchase);

        return to_route('tenant.purchase-items.index')->with('status', 'Deleted.');
    }

    private function ensurePurchaseItemInCurrentBranch(PurchaseItem $purchaseItem): void
    {
        abort_if($purchaseItem->branch_id !== $this->currentBranchId(), 404);
    }

    private function recalculatePurchaseTotals(?Purchase $purchase): void
    {
        if (! $purchase instanceof Purchase) {
            return;
        }

        $items = $purchase->items()->get();
        $subTotal = (float) $items->sum(fn (PurchaseItem $item): float => (float) $item->qty * (float) $item->unit_cost);
        $discountTotal = (float) $items->sum(fn (PurchaseItem $item): float => (float) $item->discount_amount);
        $taxTotal = (float) $items->sum(fn (PurchaseItem $item): float => (float) $item->tax_amount);
        $shippingTotal = (float) $purchase->shipping_total;
        $grandTotal = ($subTotal - $discountTotal + $taxTotal) + $shippingTotal;
        $paidTotal = (float) $purchase->payments()->sum('amount');

        $purchase->update([
            'sub_total' => $subTotal,
            'discount_total' => $discountTotal,
            'tax_total' => $taxTotal,
            'grand_total' => $grandTotal,
            'paid_total' => $paidTotal,
            'balance_due' => $grandTotal - $paidTotal,
        ]);
    }

    /**
     * @param  Collection<int, PurchaseItem>  $purchaseItems
     */
    private function syncStockForPurchaseItems(Collection $purchaseItems, string $branchId, bool $reverse = false): void
    {
        $qtyByProduct = $purchaseItems
            ->filter(fn (PurchaseItem $item): bool => $item->product_id !== null)
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
                if ($reverse) {
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
                ? (float) $stockRow->qty_on_hand - $qty
                : (float) $stockRow->qty_on_hand + $qty;

            $stockRow->update([
                'qty_on_hand' => $adjustedQty,
            ]);

            StockMove::query()->create([
                'product_id' => $productId,
                'branch_id' => $branchId,
                'warehouse_id' => null,
                'created_by' => auth('user')->id(),
                'move_type' => $reverse ? StockMoveType::PURCHASE_RETURN->value : StockMoveType::PURCHASE->value,
                'qty' => $qty,
                'unit_cost' => 0,
                'total_cost' => 0,
                'reference_type' => null,
                'reference_id' => null,
                'remarks' => $reverse ? 'Stock reversed from purchase item change/delete.' : 'Stock increased from purchase item.',
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
            'purchases' => Purchase::query()->where('branch_id', $branchId)->latest('purchase_date')->get(),
            'products' => Product::query()->orderBy('name')->get(),
            'taxes' => Tax::query()->orderBy('name')->get(),
        ];
    }
}

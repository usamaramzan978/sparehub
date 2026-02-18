<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\PurchaseItemRequest;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Tax;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

        $purchaseItem->update($payload);
        $this->recalculatePurchaseTotals($purchaseItem->purchase);

        return to_route('tenant.purchase-items.index')->with('status', 'Updated.');
    }

    public function destroy(PurchaseItem $purchaseItem): RedirectResponse
    {
        $this->ensurePurchaseItemInCurrentBranch($purchaseItem);
        $purchase = $purchaseItem->purchase;
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

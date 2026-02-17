<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\PurchaseReturnItemRequest;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Tax;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
        $this->recalculatePurchaseReturnTotals($item->purchaseReturn);

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

        $purchaseReturnItem->update($payload);
        $this->recalculatePurchaseReturnTotals($purchaseReturnItem->purchaseReturn);

        return to_route('tenant.purchase-return-items.index')->with('status', 'Updated.');
    }

    public function destroy(PurchaseReturnItem $purchaseReturnItem): RedirectResponse
    {
        $this->ensurePurchaseReturnItemInCurrentBranch($purchaseReturnItem);

        $purchaseReturn = $purchaseReturnItem->purchaseReturn;
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

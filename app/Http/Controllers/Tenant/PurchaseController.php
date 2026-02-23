<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\PurchaseStatus;
use App\Enums\StockMoveType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\PurchaseRequest;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMove;
use App\Models\Tax;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Support\AuditTimelineLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

final class PurchaseController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $perPage = min(max($request->integer('per_page', 15), 5), 100);
        $search = mb_trim($request->string('search')->toString());

        $items = Purchase::query()
            ->with(['vendor', 'warehouse'])
            ->where('branch_id', $branchId)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('purchase_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('vendor_invoice_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('vendor', fn (Builder $q) => $q->where('name', 'like', sprintf('%%%s%%', $search)));
                });
            })
            ->latest('purchase_date')
            ->paginate($perPage)
            ->withQueryString();

        return view('tenants.purchases.index', [
            'items' => $items,
        ]);
    }

    public function create(): View
    {
        return view('tenants.purchases.create', $this->formOptions());
    }

    public function store(PurchaseRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $branchId = $this->currentBranchId();
        $items = $payload['items'];
        unset($payload['items']);

        $payload['branch_id'] = $branchId;
        $payload['created_by'] = auth('user')->id();
        $createdPurchase = null;

        Purchase::query()->getConnection()->transaction(function () use ($payload, $items, $branchId, &$createdPurchase): void {
            $purchase = Purchase::query()->create($payload);
            $this->syncPurchaseItems($purchase, $items, $branchId);
            $createdPurchase = $purchase;
        });

        if ($createdPurchase instanceof Purchase) {
            AuditTimelineLogger::log(
                event: 'purchase_created',
                description: 'Purchase invoice created.',
                causer: Auth::guard('user')->user(),
                subject: $createdPurchase,
                properties: [
                    'purchase_id' => (string) $createdPurchase->id,
                    'purchase_no' => $createdPurchase->purchase_no,
                    'grand_total' => (float) $createdPurchase->grand_total,
                    'status' => $createdPurchase->status->value,
                ],
            );
        }

        return to_route('tenant.purchases.index')->with('status', 'Created.');
    }

    public function show(Purchase $purchase): View
    {
        $this->ensurePurchaseInCurrentBranch($purchase);

        $purchase->load([
            'vendor',
            'warehouse',
            'creator',
            'items.product',
            'items.tax',
            'returns.vendor',
            'payments.vendor',
            'payments.creator',
        ]);

        return view('tenants.purchases.show', [
            'purchase' => $purchase,
        ]);
    }

    public function edit(Purchase $purchase): View
    {
        $this->ensurePurchaseInCurrentBranch($purchase);
        $purchase->load('items');

        return view('tenants.purchases.edit', array_merge(
            ['purchase' => $purchase],
            $this->formOptions()
        ));
    }

    public function update(PurchaseRequest $request, Purchase $purchase): RedirectResponse
    {
        $this->ensurePurchaseInCurrentBranch($purchase);

        $payload = $request->validated();
        $branchId = $this->currentBranchId();
        $items = $payload['items'];
        unset($payload['items']);

        $payload['branch_id'] = $branchId;

        Purchase::query()->getConnection()->transaction(function () use ($purchase, $payload, $items, $branchId): void {
            $purchase->update($payload);
            $this->syncPurchaseItems($purchase, $items, $branchId);
        });

        AuditTimelineLogger::log(
            event: 'purchase_updated',
            description: 'Purchase invoice updated.',
            causer: Auth::guard('user')->user(),
            subject: $purchase,
            properties: [
                'purchase_id' => (string) $purchase->id,
                'purchase_no' => $purchase->purchase_no,
                'changed_attributes' => array_keys($payload),
            ],
        );

        return to_route('tenant.purchases.index')->with('status', 'Updated.');
    }

    public function destroy(Purchase $purchase): RedirectResponse
    {
        $this->ensurePurchaseInCurrentBranch($purchase);
        $snapshot = [
            'purchase_id' => (string) $purchase->id,
            'purchase_no' => $purchase->purchase_no,
            'grand_total' => (float) $purchase->grand_total,
        ];
        $this->syncStockForPurchaseItems($purchase->items()->get(), $purchase->branch_id, reverse: true);
        $purchase->delete();

        AuditTimelineLogger::log(
            event: 'purchase_deleted',
            description: 'Purchase invoice deleted.',
            causer: Auth::guard('user')->user(),
            subject: $purchase,
            properties: $snapshot,
        );

        return to_route('tenant.purchases.index')->with('status', 'Deleted.');
    }

    private function ensurePurchaseInCurrentBranch(Purchase $purchase): void
    {
        abort_if($purchase->branch_id !== $this->currentBranchId(), 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        $branchId = $this->currentBranchId();

        return [
            'vendors' => Vendor::query()->where('branch_id', $branchId)->orderBy('name')->get(),
            'warehouses' => Warehouse::query()->where('branch_id', $branchId)->orderBy('name')->get(),
            'products' => Product::query()->orderBy('name')->get(),
            'taxes' => Tax::query()->orderBy('name')->get(),
            'statuses' => PurchaseStatus::cases(),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncPurchaseItems(Purchase $purchase, array $items, string $branchId): void
    {
        $existingItems = $purchase->items()->get();
        $this->syncStockForPurchaseItems($existingItems, $branchId, reverse: true);
        $purchase->items()->delete();

        foreach ($items as $item) {
            $qty = (float) $item['qty'];
            $unitCost = (float) $item['unit_cost'];
            $discountAmount = (float) ($item['discount_amount'] ?? 0);
            $taxAmount = (float) ($item['tax_amount'] ?? 0);
            $lineTotal = ($qty * $unitCost) - $discountAmount + $taxAmount;

            PurchaseItem::query()->create([
                'purchase_id' => $purchase->id,
                'branch_id' => $branchId,
                'product_id' => $item['product_id'],
                'tax_id' => $item['tax_id'] ?? null,
                'qty' => $qty,
                'received_qty' => $qty,
                'unit_cost' => $unitCost,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'line_total' => $lineTotal,
                'remarks' => $item['remarks'] ?? null,
            ]);
        }

        $createdItems = $purchase->items()->get();
        $this->syncStockForPurchaseItems($createdItems, $branchId);
        $subTotal = (float) $createdItems->sum(fn (PurchaseItem $item): float => (float) $item->qty * (float) $item->unit_cost);
        $discountTotal = (float) $createdItems->sum(fn (PurchaseItem $item): float => (float) $item->discount_amount);
        $taxTotal = (float) $createdItems->sum(fn (PurchaseItem $item): float => (float) $item->tax_amount);
        $shippingTotal = (float) $purchase->shipping_total;
        $grandTotal = $subTotal - $discountTotal + $taxTotal + $shippingTotal;
        $paidTotal = (float) $purchase->paid_total;

        $purchase->update([
            'sub_total' => $subTotal,
            'discount_total' => $discountTotal,
            'tax_total' => $taxTotal,
            'grand_total' => $grandTotal,
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
                'remarks' => $reverse ? 'Stock reversed from purchase change/delete.' : 'Stock increased from purchase.',
                'occurred_at' => now(),
            ]);
        }
    }
}

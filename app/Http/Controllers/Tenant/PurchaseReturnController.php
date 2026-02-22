<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\PurchaseReturnStatus;
use App\Enums\StockMoveType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\PurchaseReturnRequest;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\StockMove;
use App\Models\Tax;
use App\Models\Vendor;
use App\Support\AuditTimelineLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

final class PurchaseReturnController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $perPage = min(max($request->integer('per_page', 15), 5), 100);
        $search = mb_trim($request->string('search')->toString());

        $items = PurchaseReturn::query()
            ->with(['vendor', 'purchase'])
            ->where('branch_id', $branchId)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('return_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('vendor', fn (Builder $q) => $q->where('name', 'like', sprintf('%%%s%%', $search)));
                });
            })
            ->latest('return_date')
            ->paginate($perPage)
            ->withQueryString();

        return view('tenants.purchase-returns.index', [
            'items' => $items,
        ]);
    }

    public function create(): View
    {
        return view('tenants.purchase-returns.create', $this->formOptions());
    }

    public function store(PurchaseReturnRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $items = $payload['items'];
        unset($payload['items']);

        $payload['branch_id'] = $this->currentBranchId();
        $payload['created_by'] = auth('user')->id();
        $createdReturn = null;

        PurchaseReturn::query()->getConnection()->transaction(function () use ($payload, $items, &$createdReturn): void {
            $purchaseReturn = PurchaseReturn::query()->create($payload);
            $this->syncPurchaseReturnItems($purchaseReturn, $items);
            $createdReturn = $purchaseReturn;
        });

        if ($createdReturn instanceof PurchaseReturn) {
            AuditTimelineLogger::log(
                event: 'purchase_return_created',
                description: 'Purchase return created.',
                causer: Auth::guard('user')->user(),
                subject: $createdReturn,
                properties: [
                    'purchase_return_id' => (string) $createdReturn->id,
                    'return_no' => $createdReturn->return_no,
                    'grand_total' => (float) $createdReturn->grand_total,
                ],
            );
        }

        return to_route('tenant.purchase-returns.index')->with('status', 'Created.');
    }

    public function show(PurchaseReturn $purchaseReturn): View
    {
        $this->ensurePurchaseReturnInCurrentBranch($purchaseReturn);

        $purchaseReturn->load([
            'vendor',
            'purchase',
            'creator',
            'items.product',
            'items.tax',
            'items.purchaseItem',
        ]);

        return view('tenants.purchase-returns.show', [
            'purchaseReturn' => $purchaseReturn,
        ]);
    }

    public function edit(PurchaseReturn $purchaseReturn): View
    {
        $this->ensurePurchaseReturnInCurrentBranch($purchaseReturn);
        $purchaseReturn->load('items');

        return view('tenants.purchase-returns.edit', array_merge(
            ['purchaseReturn' => $purchaseReturn],
            $this->formOptions()
        ));
    }

    public function update(PurchaseReturnRequest $request, PurchaseReturn $purchaseReturn): RedirectResponse
    {
        $this->ensurePurchaseReturnInCurrentBranch($purchaseReturn);

        $payload = $request->validated();
        $items = $payload['items'];
        unset($payload['items']);

        $payload['branch_id'] = $this->currentBranchId();

        PurchaseReturn::query()->getConnection()->transaction(function () use ($purchaseReturn, $payload, $items): void {
            $purchaseReturn->update($payload);
            $this->syncPurchaseReturnItems($purchaseReturn, $items);
        });

        AuditTimelineLogger::log(
            event: 'purchase_return_updated',
            description: 'Purchase return updated.',
            causer: Auth::guard('user')->user(),
            subject: $purchaseReturn,
            properties: [
                'purchase_return_id' => (string) $purchaseReturn->id,
                'return_no' => $purchaseReturn->return_no,
                'changed_attributes' => array_keys($payload),
            ],
        );

        return to_route('tenant.purchase-returns.index')->with('status', 'Updated.');
    }

    public function destroy(PurchaseReturn $purchaseReturn): RedirectResponse
    {
        $this->ensurePurchaseReturnInCurrentBranch($purchaseReturn);
        $snapshot = [
            'purchase_return_id' => (string) $purchaseReturn->id,
            'return_no' => $purchaseReturn->return_no,
            'grand_total' => (float) $purchaseReturn->grand_total,
        ];
        $this->syncStockForPurchaseReturnItems($purchaseReturn->items()->get(), $purchaseReturn->branch_id, reverse: true);
        $purchaseReturn->delete();

        AuditTimelineLogger::log(
            event: 'purchase_return_deleted',
            description: 'Purchase return deleted.',
            causer: Auth::guard('user')->user(),
            subject: $purchaseReturn,
            properties: $snapshot,
        );

        return to_route('tenant.purchase-returns.index')->with('status', 'Deleted.');
    }

    private function ensurePurchaseReturnInCurrentBranch(PurchaseReturn $purchaseReturn): void
    {
        abort_if($purchaseReturn->branch_id !== $this->currentBranchId(), 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        $branchId = $this->currentBranchId();

        return [
            'vendors' => Vendor::query()->where('branch_id', $branchId)->orderBy('name')->get(),
            'purchases' => Purchase::query()->where('branch_id', $branchId)->latest('purchase_date')->get(),
            'purchaseItems' => PurchaseItem::query()
                ->with(['purchase', 'product'])
                ->where('branch_id', $branchId)
                ->latest()
                ->get(),
            'products' => Product::query()->orderBy('name')->get(),
            'taxes' => Tax::query()->orderBy('name')->get(),
            'statuses' => PurchaseReturnStatus::cases(),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncPurchaseReturnItems(PurchaseReturn $purchaseReturn, array $items): void
    {
        $existingItems = $purchaseReturn->items()->get();
        $this->syncStockForPurchaseReturnItems($existingItems, $purchaseReturn->branch_id, reverse: true);
        $purchaseReturn->items()->delete();

        foreach ($items as $item) {
            $qty = (float) $item['qty'];
            $unitCost = (float) $item['unit_cost'];
            $taxAmount = (float) ($item['tax_amount'] ?? 0);
            $lineTotal = ($qty * $unitCost) + $taxAmount;

            PurchaseReturnItem::query()->create([
                'purchase_return_id' => $purchaseReturn->id,
                'purchase_item_id' => ($item['purchase_item_id'] ?? null) ?: null,
                'product_id' => $item['product_id'],
                'tax_id' => ($item['tax_id'] ?? null) ?: null,
                'qty' => $qty,
                'unit_cost' => $unitCost,
                'tax_amount' => $taxAmount,
                'line_total' => $lineTotal,
                'remarks' => $item['remarks'] ?? null,
            ]);
        }

        $createdItems = $purchaseReturn->items()->get();
        $this->syncStockForPurchaseReturnItems($createdItems, $purchaseReturn->branch_id);
        $subTotal = (float) $createdItems->sum(fn (PurchaseReturnItem $item): float => (float) $item->qty * (float) $item->unit_cost);
        $taxTotal = (float) $createdItems->sum(fn (PurchaseReturnItem $item): float => (float) $item->tax_amount);
        $grandTotal = (float) $createdItems->sum(fn (PurchaseReturnItem $item): float => (float) $item->line_total);

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
                'remarks' => $reverse ? 'Stock restored from purchase return change/delete.' : 'Stock reduced by purchase return.',
                'occurred_at' => now(),
            ]);
        }
    }
}

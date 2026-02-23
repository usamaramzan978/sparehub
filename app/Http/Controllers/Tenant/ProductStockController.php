<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\RecordStatus;
use App\Enums\StockMoveType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\ProductStockAdjustmentRequest;
use App\Models\Category;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\StockMove;
use App\Support\AuditTimelineLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

final class ProductStockController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $search = mb_trim($request->string('search')->toString());
        $showInactive = $request->boolean('show_inactive');

        $products = Product::query()
            ->with(['category:id,name', 'brand:id,name', 'defaultUnit:id,name'])
            ->unless($showInactive, fn ($query) => $query->where('status', RecordStatus::ACTIVE->value))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder
                        ->where('name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('sku', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('part_number', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('barcode', 'like', sprintf('%%%s%%', $search));
                });
            })
            ->orderBy('name')
            ->get();

        $productIds = $products->pluck('id')->all();

        $pricesByProduct = ProductPrice::query()
            ->where('branch_id', $branchId)
            ->whereIn('product_id', $productIds)
            ->orderByDesc('effective_from')
            ->get()
            ->groupBy('product_id');

        $stocksByProduct = InventoryStock::query()
            ->where('branch_id', $branchId)
            ->whereIn('product_id', $productIds)
            ->get()
            ->groupBy('product_id');

        $categoryIds = $products
            ->pluck('category_id')
            ->filter(fn ($value): bool => is_string($value) && $value !== '')
            ->unique()
            ->values()
            ->all();

        $categoriesById = Category::query()
            ->whereIn('id', $categoryIds)
            ->orderBy('name')
            ->get()
            ->keyBy('id');

        $inventoryTree = collect();
        $groupedByCategory = $products->groupBy(fn (Product $product): string => (string) ($product->category_id ?: 'uncategorized'));

        foreach ($groupedByCategory as $categoryKey => $categoryProducts) {
            $categoryName = $categoryKey === 'uncategorized'
                ? 'Uncategorized'
                : (string) ($categoriesById->get($categoryKey)?->name ?? 'Unknown Category');

            $productRows = collect();
            foreach ($categoryProducts->sortBy('name')->values() as $product) {
                if (! $product instanceof Product) {
                    continue;
                }

                $latestPrice = $this->resolveLatestPrice($pricesByProduct->get($product->id));
                $stockRows = $stocksByProduct->get($product->id, collect())->values();
                $qtyOnHand = (float) $stockRows->sum(fn (InventoryStock $stock): float => (float) $stock->qty_on_hand);
                $qtyReserved = (float) $stockRows->sum(fn (InventoryStock $stock): float => (float) $stock->qty_reserved);

                $productRows->push([
                    'product' => $product,
                    'latest_price' => $latestPrice,
                    'stock_rows' => $stockRows,
                    'qty_on_hand' => $qtyOnHand,
                    'qty_reserved' => $qtyReserved,
                    'qty_available' => $qtyOnHand - $qtyReserved,
                ]);
            }

            $inventoryTree->push([
                'category_key' => $categoryKey,
                'category_name' => $categoryName,
                'products_count' => $productRows->count(),
                'products' => $productRows,
            ]);
        }

        $inventoryTree = $inventoryTree
            ->sortBy('category_name')
            ->values();

        $summary = [
            'categories_count' => $inventoryTree->count(),
            'products_count' => $products->count(),
            'qty_on_hand_total' => (float) $stocksByProduct->flatten()->sum(fn (InventoryStock $stock): float => (float) $stock->qty_on_hand),
            'qty_reserved_total' => (float) $stocksByProduct->flatten()->sum(fn (InventoryStock $stock): float => (float) $stock->qty_reserved),
        ];
        $summary['qty_available_total'] = $summary['qty_on_hand_total'] - $summary['qty_reserved_total'];

        return view('tenants.products.stock.index', [
            'search' => $search,
            'showInactive' => $showInactive,
            'inventoryTree' => $inventoryTree,
            'summary' => $summary,
        ]);
    }

    public function adjustments(): View
    {
        $branchId = $this->currentBranchId();

        return view('tenants.products.stock.adjustments', [
            'adjustableProducts' => $this->adjustableProducts($branchId),
        ]);
    }

    public function storeAdjustment(ProductStockAdjustmentRequest $request): RedirectResponse
    {
        $branchId = $this->currentBranchId();
        $payload = $request->validated();
        $productId = (string) $payload['product_id'];
        $action = (string) $payload['action'];
        $qty = (float) $payload['qty'];
        $remarks = isset($payload['remarks']) ? (string) $payload['remarks'] : null;
        $adjustmentSummary = [
            'previous_qty' => 0.0,
            'new_qty' => 0.0,
            'move_qty' => 0.0,
            'move_type' => null,
        ];

        InventoryStock::query()->getConnection()->transaction(function () use ($action, $branchId, $productId, $qty, $remarks, &$adjustmentSummary): void {
            $stockRow = InventoryStock::query()
                ->where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->first();

            if (! $stockRow instanceof InventoryStock) {
                $stockRow = InventoryStock::query()->create([
                    'branch_id' => $branchId,
                    'product_id' => $productId,
                    'qty_on_hand' => 0,
                    'qty_reserved' => 0,
                    'avg_cost' => 0,
                ]);
            }

            $currentQty = (float) $stockRow->qty_on_hand;
            $newQty = $currentQty;
            $moveType = StockMoveType::ADJUSTMENT_IN;
            $moveQty = 0.0;

            if ($action === 'in') {
                $newQty = $currentQty + $qty;
                $moveType = StockMoveType::ADJUSTMENT_IN;
                $moveQty = $qty;
            } elseif ($action === 'out') {
                $newQty = $currentQty - $qty;
                if ($newQty < 0) {
                    throw ValidationException::withMessages([
                        'qty' => 'Stock out quantity exceeds available stock.',
                    ]);
                }

                $moveType = StockMoveType::ADJUSTMENT_OUT;
                $moveQty = $qty;
            } else {
                $newQty = $qty;
                $delta = $newQty - $currentQty;
                if ($delta > 0) {
                    $moveType = StockMoveType::ADJUSTMENT_IN;
                } elseif ($delta < 0) {
                    $moveType = StockMoveType::ADJUSTMENT_OUT;
                }

                $moveQty = abs($delta);
            }

            $stockRow->update([
                'qty_on_hand' => $newQty,
            ]);

            if ($moveQty > 0) {
                StockMove::query()->create([
                    'product_id' => $productId,
                    'branch_id' => $branchId,
                    'warehouse_id' => null,
                    'created_by' => auth('user')->id(),
                    'move_type' => $moveType->value,
                    'qty' => $moveQty,
                    'unit_cost' => 0,
                    'total_cost' => 0,
                    'reference_type' => null,
                    'reference_id' => null,
                    'remarks' => $remarks,
                    'occurred_at' => now(),
                ]);
            }

            $adjustmentSummary = [
                'previous_qty' => $currentQty,
                'new_qty' => $newQty,
                'move_qty' => $moveQty,
                'move_type' => $moveType->value,
            ];
        });

        $product = Product::query()->find($productId);
        AuditTimelineLogger::log(
            event: 'inventory_stock_adjusted',
            description: 'Inventory stock adjusted.',
            causer: Auth::guard('user')->user(),
            subject: $product,
            properties: [
                'product_id' => $productId,
                'product_name' => (string) ($product?->name ?? ''),
                'action' => $action,
                'requested_qty' => $qty,
                'move_qty' => $adjustmentSummary['move_qty'],
                'move_type' => $adjustmentSummary['move_type'],
                'previous_qty' => $adjustmentSummary['previous_qty'],
                'new_qty' => $adjustmentSummary['new_qty'],
                'remarks' => $remarks,
            ],
        );

        return to_route('tenant.products.stock.adjustments')
            ->with('status', 'Stock adjusted.');
    }

    /**
     * @return Collection<int, Product>
     */
    private function adjustableProducts(string $branchId): Collection
    {
        $products = Product::query()
            ->where('track_stock', true)
            ->where('status', RecordStatus::ACTIVE->value)
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        $openingByProduct = StockMove::query()
            ->where('branch_id', $branchId)
            ->whereIn('product_id', $products->pluck('id')->all())
            ->where('move_type', StockMoveType::OPENING->value)
            ->selectRaw('product_id, SUM(qty) as opening_qty')
            ->groupBy('product_id')
            ->pluck('opening_qty', 'product_id');

        $firstMoveByProduct = StockMove::query()
            ->where('branch_id', $branchId)
            ->whereIn('product_id', $products->pluck('id')->all())
            ->oldest('occurred_at')->oldest()
            ->get(['product_id', 'qty'])
            ->groupBy('product_id')
            ->map(fn (Collection $moves): float => (float) $moves->first()?->qty ?: 0.0);

        $stockByProduct = InventoryStock::query()
            ->where('branch_id', $branchId)
            ->whereIn('product_id', $products->pluck('id')->all())
            ->selectRaw('product_id, SUM(qty_on_hand - qty_reserved) as qty_available')
            ->groupBy('product_id')
            ->pluck('qty_available', 'product_id');

        $onHandByProduct = InventoryStock::query()
            ->where('branch_id', $branchId)
            ->whereIn('product_id', $products->pluck('id')->all())
            ->selectRaw('product_id, SUM(qty_on_hand) as qty_on_hand')
            ->groupBy('product_id')
            ->pluck('qty_on_hand', 'product_id');

        return $products->map(function (Product $product) use ($firstMoveByProduct, $onHandByProduct, $openingByProduct, $stockByProduct): Product {
            $qtyOnHand = (float) ($onHandByProduct[$product->id] ?? 0.0);
            $product->setAttribute('qty_available', (float) ($stockByProduct[$product->id] ?? 0.0));
            $openingFromMove = (float) ($openingByProduct[$product->id] ?? 0.0);
            $openingFromFirstMove = (float) ($firstMoveByProduct[$product->id] ?? 0.0);
            $openingStock = $openingFromMove > 0 ? $openingFromMove : ($openingFromFirstMove > 0 ? $openingFromFirstMove : $qtyOnHand);
            $product->setAttribute('opening_stock', $openingStock);

            return $product;
        });
    }

    /**
     * @param  Collection<int, ProductPrice>|null  $prices
     */
    private function resolveLatestPrice(?Collection $prices): ?ProductPrice
    {
        $latestPrice = $prices?->first();

        return $latestPrice instanceof ProductPrice ? $latestPrice : null;
    }
}

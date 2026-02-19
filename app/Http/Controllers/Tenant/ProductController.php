<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\RecordStatus;
use App\Enums\StockMoveType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\ProductRequest;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\StockMove;
use App\Models\Tax;
use App\Models\Unit;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $perPage = min(max($request->integer('per_page', 15), 5), 100);
        $search = mb_trim($request->string('search')->toString());

        $products = Product::query()
            ->with(['category', 'brand', 'defaultUnit', 'defaultTax'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('sku', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('part_number', 'like', sprintf('%%%s%%', $search));
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $productIds = $products->getCollection()->pluck('id')->all();

        $stockByProduct = InventoryStock::query()
            ->where('branch_id', $branchId)
            ->whereIn('product_id', $productIds)
            ->selectRaw('product_id, SUM(qty_on_hand) as qty_on_hand')
            ->groupBy('product_id')
            ->pluck('qty_on_hand', 'product_id');

        $openingByProduct = StockMove::query()
            ->where('branch_id', $branchId)
            ->whereIn('product_id', $productIds)
            ->where('move_type', StockMoveType::OPENING->value)
            ->selectRaw('product_id, SUM(qty) as opening_qty')
            ->groupBy('product_id')
            ->pluck('opening_qty', 'product_id');

        $firstMoveByProduct = StockMove::query()
            ->where('branch_id', $branchId)
            ->whereIn('product_id', $productIds)
            ->oldest('occurred_at')->oldest()
            ->get(['product_id', 'qty'])
            ->groupBy('product_id')
            ->map(fn (Collection $moves): float => (float) $moves->first()?->qty ?: 0.0);

        $products->setCollection(
            $products->getCollection()->map(function (Product $product) use ($firstMoveByProduct, $openingByProduct, $stockByProduct): Product {
                $qtyOnHand = (float) ($stockByProduct[$product->id] ?? 0.0);
                $openingFromMove = (float) ($openingByProduct[$product->id] ?? 0.0);
                $openingFromFirstMove = (float) ($firstMoveByProduct[$product->id] ?? 0.0);
                $openingStock = $openingFromMove > 0 ? $openingFromMove : ($openingFromFirstMove > 0 ? $openingFromFirstMove : $qtyOnHand);
                $product->setAttribute('qty_on_hand', $qtyOnHand);
                $product->setAttribute('opening_stock', $openingStock);

                return $product;
            })
        );

        return view('tenants.products.index', ['items' => $products]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $openingStock = (float) ($payload['opening_stock'] ?? 0);
        unset($payload['opening_stock']);

        $branchId = $this->currentBranchId();

        Product::query()->getConnection()->transaction(function () use ($branchId, $openingStock, $payload): void {
            $product = Product::query()->create($payload);
            $this->syncProductOpeningStock($product, $branchId, $openingStock, isNewProduct: true);
        });

        return to_route('tenant.products.index')
            ->with('status', 'Created.');
    }

    public function create(): View
    {
        $categories = Category::query()->orderBy('name')->get();
        $brands = Brand::query()->orderBy('name')->get();
        $taxes = Tax::query()
            ->where('status', RecordStatus::ACTIVE->value)
            ->orderBy('name')
            ->get();
        $units = Unit::query()
            ->where('status', RecordStatus::ACTIVE->value)
            ->orderBy('name')
            ->get();
        $statuses = RecordStatus::cases();

        return view('tenants.products.create', [
            'categories' => $categories,
            'brands' => $brands,
            'taxes' => $taxes,
            'units' => $units,
            'statuses' => $statuses,
        ]);
    }

    public function show(Product $product): View
    {
        $branchId = $this->currentBranchId();

        $product->load(['category', 'brand', 'defaultTax', 'defaultUnit']);

        $branch = Branch::query()
            ->with('warehouse')
            ->find($branchId);

        $priceHistory = ProductPrice::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $product->id)
            ->latest('effective_from')
            ->get();

        $latestPrice = $priceHistory->first();

        $stockOnHand = (float) InventoryStock::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $product->id)
            ->sum('qty_on_hand');

        $stockReserved = (float) InventoryStock::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $product->id)
            ->sum('qty_reserved');

        /** @var Collection<int, array{name:string,sku:string,on_hand:float,cost:float,sale:float,status:string,effective_from:string|null}> $priceRows */
        $priceRows = $priceHistory->map(fn (ProductPrice $price): array => [
            'name' => $product->name,
            'sku' => $product->sku,
            'on_hand' => $stockOnHand,
            'cost' => (float) $price->cost,
            'sale' => (float) $price->retail_price,
            'status' => $product->status->value,
            'effective_from' => $price->effective_from?->format('Y-m-d H:i'),
        ]);

        return view('tenants.products.show', [
            'product' => $product,
            'branch' => $branch,
            'latestPrice' => $latestPrice,
            'stockOnHand' => $stockOnHand,
            'stockReserved' => $stockReserved,
            'priceRows' => $priceRows,
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $payload = $request->validated();
        $openingStock = (float) ($payload['opening_stock'] ?? 0);
        unset($payload['opening_stock']);

        $branchId = $this->currentBranchId();

        Product::query()->getConnection()->transaction(function () use ($branchId, $openingStock, $payload, $product): void {
            $product->update($payload);
            $this->syncProductOpeningStock($product, $branchId, $openingStock);
        });

        return to_route('tenant.products.index')
            ->with('status', 'Updated.');
    }

    public function edit(Product $product): View
    {
        $categories = Category::query()->orderBy('name')->get();
        $brands = Brand::query()->orderBy('name')->get();
        $taxes = Tax::query()
            ->where('status', RecordStatus::ACTIVE->value)
            ->orderBy('name')
            ->get();
        $units = Unit::query()
            ->where('status', RecordStatus::ACTIVE->value)
            ->orderBy('name')
            ->get();
        $statuses = RecordStatus::cases();
        $stockOnHand = (float) InventoryStock::query()
            ->where('branch_id', $this->currentBranchId())
            ->where('product_id', $product->id)
            ->sum('qty_on_hand');

        return view('tenants.products.edit', [
            'product' => $product,
            'categories' => $categories,
            'brands' => $brands,
            'taxes' => $taxes,
            'units' => $units,
            'statuses' => $statuses,
            'stockOnHand' => $stockOnHand,
        ]);
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return to_route('tenant.products.index')
            ->with('status', 'Deleted.');
    }

    private function syncProductOpeningStock(Product $product, string $branchId, float $targetStock, bool $isNewProduct = false): void
    {
        if (! $product->track_stock) {
            return;
        }

        $stockRows = InventoryStock::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $product->id)
            ->oldest('updated_at')
            ->get();

        $currentTotal = (float) $stockRows->sum(fn (InventoryStock $stock): float => (float) $stock->qty_on_hand);
        $normalizedTarget = max(0, $targetStock);

        if ($stockRows->isEmpty()) {
            $stockRow = InventoryStock::query()->create([
                'branch_id' => $branchId,
                'product_id' => $product->id,
                'qty_on_hand' => $normalizedTarget,
                'qty_reserved' => 0,
                'avg_cost' => 0,
            ]);
        } else {
            /** @var InventoryStock $stockRow */
            $stockRow = $stockRows->first();
            $stockRow->update([
                'qty_on_hand' => $normalizedTarget,
            ]);
            $stockRows
                ->slice(1)
                ->each(fn (InventoryStock $stock): bool => $stock->update(['qty_on_hand' => 0]));
        }

        $delta = $normalizedTarget - $currentTotal;
        if ($delta === 0.0) {
            return;
        }

        $moveType = $isNewProduct
            ? StockMoveType::OPENING
            : ($delta > 0 ? StockMoveType::ADJUSTMENT_IN : StockMoveType::ADJUSTMENT_OUT);

        StockMove::query()->create([
            'product_id' => $product->id,
            'branch_id' => $branchId,
            'warehouse_id' => null,
            'created_by' => auth('user')->id(),
            'move_type' => $moveType->value,
            'qty' => abs($delta),
            'unit_cost' => 0,
            'total_cost' => 0,
            'reference_type' => null,
            'reference_id' => null,
            'remarks' => $isNewProduct ? 'Opening stock from product create form.' : 'Stock adjusted from product edit form.',
            'occurred_at' => now(),
        ]);
    }
}

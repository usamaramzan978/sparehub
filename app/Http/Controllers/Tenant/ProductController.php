<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\ProductRequest;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\ProductPrice;
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

        return view('tenants.products.index', ['items' => $products]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        Product::query()->create($request->validated());

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
        $product->update($request->validated());

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

        return view('tenants.products.edit', [
            'product' => $product,
            'categories' => $categories,
            'brands' => $brands,
            'taxes' => $taxes,
            'units' => $units,
            'statuses' => $statuses,
        ]);
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return to_route('tenant.products.index')
            ->with('status', 'Deleted.');
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\ProductPrice\CreateProductPriceAction;
use App\Actions\Tenant\ProductPrice\DeleteProductPriceAction;
use App\Actions\Tenant\ProductPrice\EnsureProductPriceInBranchAction;
use App\Actions\Tenant\ProductPrice\UpdateProductPriceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\ProductPriceRequest;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ProductPriceController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $perPage = min(max($request->integer('per_page', 15), 5), 100);
        $search = mb_trim($request->string('search')->toString());

        $prices = ProductPrice::query()
            ->with('product')
            ->where('branch_id', $branchId)
            ->when(filled($search), function (Builder $query) use ($search): void {
                $query->whereHas('product', function (Builder $builder) use ($search): void {
                    $builder
                        ->where('name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('sku', 'like', sprintf('%%%s%%', $search));
                });
            })
            ->latest('effective_from')
            ->paginate($perPage)
            ->withQueryString();

        $products = Product::query()
            ->orderBy('name')
            ->get();

        return view('tenants.product-prices.index', [
            'items' => $prices,
            'products' => $products,
        ]);
    }

    public function store(ProductPriceRequest $request, CreateProductPriceAction $action): RedirectResponse
    {
        $action->handle($request->validated(), $this->currentBranchId());

        return to_route('tenant.product-prices.index')
            ->with('status', 'Created.');
    }

    public function show(ProductPrice $productPrice, EnsureProductPriceInBranchAction $ensureProductPriceInBranchAction): View
    {
        $productPrice = $ensureProductPriceInBranchAction->handle($productPrice, $this->currentBranchId());

        $productPrice->load(['product', 'branch']);

        return view('tenants.product-prices.show', [
            'productPrice' => $productPrice,
        ]);
    }

    public function update(
        ProductPriceRequest $request,
        ProductPrice $productPrice,
        UpdateProductPriceAction $action,
        EnsureProductPriceInBranchAction $ensureProductPriceInBranchAction
    ): RedirectResponse {
        $productPrice = $ensureProductPriceInBranchAction->handle($productPrice, $this->currentBranchId());
        $action->handle($productPrice, $request->validated(), $this->currentBranchId());

        return to_route('tenant.product-prices.index')
            ->with('status', 'Updated.');
    }

    public function destroy(
        ProductPrice $productPrice,
        DeleteProductPriceAction $action,
        EnsureProductPriceInBranchAction $ensureProductPriceInBranchAction
    ): RedirectResponse {
        $productPrice = $ensureProductPriceInBranchAction->handle($productPrice, $this->currentBranchId());
        $action->handle($productPrice);

        return to_route('tenant.product-prices.index')
            ->with('status', 'Deleted.');
    }
}

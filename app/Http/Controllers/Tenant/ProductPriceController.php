<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\ProductPriceRequest;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ProductPriceController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $perPage = min(max($request->integer('per_page', 15), 5), 100);

        $prices = ProductPrice::query()
            ->with('product')
            ->where('branch_id', $branchId)
            ->latest('effective_from')
            ->paginate($perPage);

        $products = Product::query()
            ->orderBy('name')
            ->get();

        return view('tenants.product-prices.index', [
            'items' => $prices,
            'products' => $products,
        ]);
    }

    public function store(ProductPriceRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $payload['branch_id'] = $this->currentBranchId();

        ProductPrice::query()->create($payload);

        return to_route('tenant.product-prices.index')
            ->with('status', 'Created.');
    }

    public function show(ProductPrice $productPrice): View
    {
        $this->ensurePriceInCurrentBranch($productPrice);

        $productPrice->load(['product', 'branch']);

        return view('tenants.product-prices.show', [
            'productPrice' => $productPrice,
        ]);
    }

    public function update(ProductPriceRequest $request, ProductPrice $productPrice): RedirectResponse
    {
        $this->ensurePriceInCurrentBranch($productPrice);

        $payload = $request->validated();
        $payload['branch_id'] = $this->currentBranchId();

        $productPrice->update($payload);

        return to_route('tenant.product-prices.index')
            ->with('status', 'Updated.');
    }

    public function destroy(ProductPrice $productPrice): RedirectResponse
    {
        $this->ensurePriceInCurrentBranch($productPrice);

        $productPrice->delete();

        return to_route('tenant.product-prices.index')
            ->with('status', 'Deleted.');
    }

    private function ensurePriceInCurrentBranch(ProductPrice $productPrice): void
    {
        abort_if($productPrice->branch_id !== $this->currentBranchId(), 404);
    }
}

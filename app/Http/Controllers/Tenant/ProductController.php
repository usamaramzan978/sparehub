<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\Product\BuildProductsIndexDataAction;
use App\Actions\Tenant\Product\CreateProductAction;
use App\Actions\Tenant\Product\DeleteProductAction;
use App\Actions\Tenant\Product\GetProductDetailsAction;
use App\Actions\Tenant\Product\GetProductEditDataAction;
use App\Actions\Tenant\Product\GetProductFormOptionsAction;
use App\Actions\Tenant\Product\UpdateProductAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\ProductRequest;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ProductController extends Controller
{
    public function index(Request $request, BuildProductsIndexDataAction $buildProductsIndexDataAction): View
    {
        $sortBy = $request->string('sort_by')->toString();
        $sortDirection = $request->string('sort_direction')->toString();
        $allowedSortColumns = ['name', 'sku', 'status', 'created_at'];
        $activeSortBy = in_array($sortBy, $allowedSortColumns, true) ? $sortBy : null;
        $activeSortDirection = in_array($sortDirection, ['asc', 'desc'], true) ? $sortDirection : 'asc';

        $products = $buildProductsIndexDataAction->handle(
            $request,
            $this->currentBranchId(),
            $activeSortBy,
            $activeSortDirection
        );

        return view('tenants.products.index', [
            'items' => $products,
            'sortBy' => $activeSortBy,
            'sortDirection' => $activeSortDirection,
        ]);
    }

    public function store(ProductRequest $request, CreateProductAction $createProductAction): RedirectResponse
    {
        $createProductAction->handle($request->validated(), $this->currentBranchId());

        return to_route('tenant.products.index')
            ->with('status', 'Created.');
    }

    public function create(GetProductFormOptionsAction $getProductFormOptionsAction): View
    {
        return view('tenants.products.create', $getProductFormOptionsAction->handle());
    }

    public function show(Product $product, GetProductDetailsAction $getProductDetailsAction): View
    {
        return view('tenants.products.show', $getProductDetailsAction->handle($product, $this->currentBranchId()));
    }

    public function update(ProductRequest $request, Product $product, UpdateProductAction $updateProductAction): RedirectResponse
    {
        $updateProductAction->handle($product, $request->validated(), $this->currentBranchId());

        return to_route('tenant.products.index')
            ->with('status', 'Updated.');
    }

    public function edit(Product $product, GetProductEditDataAction $getProductEditDataAction): View
    {
        return view('tenants.products.edit', $getProductEditDataAction->handle($product, $this->currentBranchId()));
    }

    public function destroy(Product $product, DeleteProductAction $deleteProductAction): RedirectResponse
    {
        $deleteProductAction->handle($product);

        return to_route('tenant.products.index')
            ->with('status', 'Deleted.');
    }
}

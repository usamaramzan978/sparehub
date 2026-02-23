<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\ProductStock\BuildInventoryTreeDataAction;
use App\Actions\Tenant\ProductStock\ListAdjustableProductsAction;
use App\Actions\Tenant\ProductStock\StoreProductStockAdjustmentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\ProductStockAdjustmentRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ProductStockController extends Controller
{
    public function index(Request $request, BuildInventoryTreeDataAction $buildInventoryTreeDataAction): View
    {
        return view('tenants.products.stock.index', $buildInventoryTreeDataAction->handle($request, $this->currentBranchId()));
    }

    public function adjustments(ListAdjustableProductsAction $listAdjustableProductsAction): View
    {
        return view('tenants.products.stock.adjustments', [
            'adjustableProducts' => $listAdjustableProductsAction->handle($this->currentBranchId()),
        ]);
    }

    public function storeAdjustment(
        ProductStockAdjustmentRequest $request,
        StoreProductStockAdjustmentAction $storeProductStockAdjustmentAction
    ): RedirectResponse {
        $storeProductStockAdjustmentAction->handle($request->validated(), $this->currentBranchId());

        return to_route('tenant.products.stock.adjustments')
            ->with('status', 'Stock adjusted.');
    }
}

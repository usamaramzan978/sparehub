<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\Product\BuildProductHistoryDataAction;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class ProductHistoryController extends Controller
{
    public function __invoke(Request $request, BuildProductHistoryDataAction $buildProductHistoryDataAction): View
    {
        return view('tenants.products.history.index', $buildProductHistoryDataAction->handle($request, $this->currentBranchId()));
    }
}

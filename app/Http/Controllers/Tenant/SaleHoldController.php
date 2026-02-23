<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\SaleHold\CreateSaleHoldAction;
use App\Actions\Tenant\SaleHold\DeleteSaleHoldAction;
use App\Actions\Tenant\SaleHold\EnsureSaleHoldInBranchAction;
use App\Actions\Tenant\SaleHold\UpdateSaleHoldAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\SaleHoldRequest;
use App\Models\Customer;
use App\Models\SaleHold;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class SaleHoldController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $perPage = min(max($request->integer('per_page', 15), 5), 100);
        $search = mb_trim($request->string('search')->toString());

        $holds = SaleHold::query()
            ->with('customer')
            ->where('branch_id', $branchId)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('hold_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('customer', fn (Builder $q) => $q->where('name', 'like', sprintf('%%%s%%', $search)));
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('tenants.sale-holds.index', [
            'items' => $holds,
            'customers' => Customer::query()->where('branch_id', $branchId)->orderBy('name')->get(),
        ]);
    }

    public function store(SaleHoldRequest $request, CreateSaleHoldAction $action): RedirectResponse
    {
        $action->handle($request->validated(), $this->currentBranchId(), auth('user')->id());

        return to_route('tenant.sale-holds.index')->with('status', 'Created.');
    }

    public function show(SaleHold $saleHold, EnsureSaleHoldInBranchAction $ensureSaleHoldInBranchAction): View
    {
        $saleHold = $ensureSaleHoldInBranchAction->handle($saleHold, $this->currentBranchId());

        $saleHold->load(['customer', 'creator', 'branch']);

        return view('tenants.sale-holds.show', [
            'saleHold' => $saleHold,
        ]);
    }

    public function update(
        SaleHoldRequest $request,
        SaleHold $saleHold,
        UpdateSaleHoldAction $action,
        EnsureSaleHoldInBranchAction $ensureSaleHoldInBranchAction
    ): RedirectResponse {
        $saleHold = $ensureSaleHoldInBranchAction->handle($saleHold, $this->currentBranchId());
        $action->handle($saleHold, $request->validated(), $this->currentBranchId());

        return to_route('tenant.sale-holds.index')->with('status', 'Updated.');
    }

    public function destroy(
        SaleHold $saleHold,
        DeleteSaleHoldAction $action,
        EnsureSaleHoldInBranchAction $ensureSaleHoldInBranchAction
    ): RedirectResponse {
        $saleHold = $ensureSaleHoldInBranchAction->handle($saleHold, $this->currentBranchId());
        $action->handle($saleHold);

        return to_route('tenant.sale-holds.index')->with('status', 'Deleted.');
    }
}

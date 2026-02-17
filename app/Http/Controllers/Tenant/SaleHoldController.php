<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

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

    public function store(SaleHoldRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $payload['branch_id'] = $this->currentBranchId();
        $payload['created_by'] = auth('user')->id();
        $payload['payload'] = json_decode((string) $payload['payload'], true, 512, JSON_THROW_ON_ERROR);

        SaleHold::query()->create($payload);

        return to_route('tenant.sale-holds.index')->with('status', 'Created.');
    }

    public function show(SaleHold $saleHold): View
    {
        $this->ensureSaleHoldInCurrentBranch($saleHold);

        $saleHold->load(['customer', 'creator', 'branch']);

        return view('tenants.sale-holds.show', [
            'saleHold' => $saleHold,
        ]);
    }

    public function update(SaleHoldRequest $request, SaleHold $saleHold): RedirectResponse
    {
        $this->ensureSaleHoldInCurrentBranch($saleHold);

        $payload = $request->validated();
        $payload['branch_id'] = $this->currentBranchId();
        $payload['payload'] = json_decode((string) $payload['payload'], true, 512, JSON_THROW_ON_ERROR);

        $saleHold->update($payload);

        return to_route('tenant.sale-holds.index')->with('status', 'Updated.');
    }

    public function destroy(SaleHold $saleHold): RedirectResponse
    {
        $this->ensureSaleHoldInCurrentBranch($saleHold);
        $saleHold->delete();

        return to_route('tenant.sale-holds.index')->with('status', 'Deleted.');
    }

    private function ensureSaleHoldInCurrentBranch(SaleHold $saleHold): void
    {
        abort_if($saleHold->branch_id !== $this->currentBranchId(), 404);
    }
}

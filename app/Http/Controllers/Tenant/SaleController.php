<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\InvoiceType;
use App\Enums\SaleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\SaleRequest;
use App\Models\Customer;
use App\Models\JobCard;
use App\Models\Sale;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class SaleController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $perPage = min(max($request->integer('per_page', 15), 5), 100);
        $search = mb_trim($request->string('search')->toString());

        $sales = Sale::query()
            ->with(['customer', 'jobCard'])
            ->where('branch_id', $branchId)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('invoice_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('customer', fn (Builder $q) => $q->where('name', 'like', sprintf('%%%s%%', $search)));
                });
            })
            ->latest('invoice_date')
            ->paginate($perPage)
            ->withQueryString();

        return view('tenants.sales.index', [
            'items' => $sales,
        ]);
    }

    public function create(): View
    {
        return view('tenants.sales.create', $this->formOptions());
    }

    public function store(SaleRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $payload['branch_id'] = $this->currentBranchId();
        $payload['created_by'] = auth('user')->id();

        Sale::query()->create($payload);

        return to_route('tenant.sales.index')->with('status', 'Created.');
    }

    public function show(Sale $sale): View
    {
        $this->ensureSaleInCurrentBranch($sale);

        $sale->load([
            'customer',
            'jobCard',
            'creator',
            'items.product',
            'items.serviceCatalog',
            'items.jobCardService',
            'payments.receiver',
        ]);

        return view('tenants.sales.show', [
            'sale' => $sale,
        ]);
    }

    public function print(Sale $sale): View
    {
        $this->ensureSaleInCurrentBranch($sale);

        $sale->load([
            'branch',
            'customer',
            'items.product',
            'items.serviceCatalog',
            'payments',
        ]);

        return view('tenants.sales.print', [
            'sale' => $sale,
        ]);
    }

    public function edit(Sale $sale): View
    {
        $this->ensureSaleInCurrentBranch($sale);

        return view('tenants.sales.edit', array_merge(
            ['sale' => $sale],
            $this->formOptions()
        ));
    }

    public function update(SaleRequest $request, Sale $sale): RedirectResponse
    {
        $this->ensureSaleInCurrentBranch($sale);

        $payload = $request->validated();
        $payload['branch_id'] = $this->currentBranchId();

        $sale->update($payload);

        return to_route('tenant.sales.index')->with('status', 'Updated.');
    }

    public function destroy(Sale $sale): RedirectResponse
    {
        $this->ensureSaleInCurrentBranch($sale);
        $sale->delete();

        return to_route('tenant.sales.index')->with('status', 'Deleted.');
    }

    private function ensureSaleInCurrentBranch(Sale $sale): void
    {
        abort_if($sale->branch_id !== $this->currentBranchId(), 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        $branchId = $this->currentBranchId();

        return [
            'customers' => Customer::query()->where('branch_id', $branchId)->orderBy('name')->get(),
            'jobCards' => JobCard::query()->where('branch_id', $branchId)->latest('job_date')->get(),
            'statuses' => SaleStatus::cases(),
            'invoiceTypes' => InvoiceType::cases(),
        ];
    }
}

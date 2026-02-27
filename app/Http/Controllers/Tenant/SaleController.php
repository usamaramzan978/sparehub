<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\Sale\CreateSaleAction;
use App\Actions\Tenant\Sale\DeleteSaleAction;
use App\Actions\Tenant\Sale\EnsureSaleInBranchAction;
use App\Actions\Tenant\Sale\UpdateSaleAction;
use App\Enums\InvoiceType;
use App\Enums\SaleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\SaleRequest;
use App\Models\Customer;
use App\Models\JobCard;
use App\Models\JobCardPart;
use App\Models\JobCardService;
use App\Models\Product;
use App\Models\Sale;
use App\Models\ServiceCatalog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class SaleController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $perPage = min(max($request->integer('per_page', 15), 5), 100);
        $search = mb_trim($request->string('search')->toString());
        $allowedSortColumns = ['invoice_no', 'invoice_date', 'invoice_type', 'status', 'grand_total', 'balance_due', 'created_at'];
        [$activeSortBy, $activeSortDirection] = $this->resolveSort($request, $allowedSortColumns);

        $salesQuery = Sale::query()
            ->with(['customer', 'jobCard'])
            ->where('branch_id', $branchId)
            ->when(filled($search), function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('invoice_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('customer', fn (Builder $q) => $q->where('name', 'like', sprintf('%%%s%%', $search)));
                });
            });

        if ($activeSortBy !== null) {
            $salesQuery->orderBy($activeSortBy, $activeSortDirection);
        } else {
            $salesQuery->latest('invoice_date');
        }

        $sales = $salesQuery
            ->paginate($perPage)
            ->withQueryString();

        return view('tenants.sales.index', [
            'items' => $sales,
            'sortBy' => $activeSortBy,
            'sortDirection' => $activeSortDirection,
        ]);
    }

    public function create(): View
    {
        return view('tenants.sales.create', $this->formOptions());
    }

    public function jobCardItems(string $jobCard): JsonResponse
    {
        $branchId = $this->currentBranchId();
        $jobCardModel = JobCard::query()
            ->withoutGlobalScopes()
            ->with([
                'services.serviceCatalog',
                'parts.product',
            ])
            ->where('branch_id', $branchId)
            ->whereKey($jobCard)
            ->first();

        if (! $jobCardModel instanceof JobCard) {
            return response()->json([
                'customer_id' => null,
                'items' => [],
            ]);
        }

        $serviceItems = $jobCardModel->services
            ->map(fn (JobCardService $service): array => [
                'line_type' => 'service',
                'product_id' => null,
                'service_catalog_id' => $service->service_catalog_id,
                'job_card_service_id' => $service->id,
                'mechanic_id' => $service->technician_id,
                'description' => $service->service_name,
                'qty' => (float) $service->qty,
                'unit_price' => (float) $service->rate,
                'discount_amount' => 0.0,
                'tax_amount' => 0.0,
                'mechanic_charge' => 0.0,
            ]);

        $partItems = $jobCardModel->parts
            ->map(fn (JobCardPart $part): array => [
                'line_type' => 'product',
                'product_id' => $part->product_id,
                'service_catalog_id' => null,
                'job_card_service_id' => null,
                'mechanic_id' => null,
                'description' => $part->product?->name,
                'qty' => (float) $part->qty,
                'unit_price' => (float) $part->unit_price,
                'discount_amount' => 0.0,
                'tax_amount' => 0.0,
                'mechanic_charge' => 0.0,
            ]);

        return response()->json([
            'customer_id' => $jobCardModel->customer_id,
            'items' => $serviceItems->concat($partItems)->values()->all(),
        ]);
    }

    public function store(SaleRequest $request, CreateSaleAction $action): RedirectResponse
    {
        $action->handle($request->validated(), $this->currentBranchId(), auth('user')->id());

        return to_route('tenant.sales.index')->with('status', 'Created.');
    }

    public function show(Sale $sale, EnsureSaleInBranchAction $ensureSaleInBranchAction): View
    {
        $sale = $ensureSaleInBranchAction->handle($sale, $this->currentBranchId());

        $sale->load([
            'customer',
            'jobCard',
            'creator',
            'items.product',
            'items.serviceCatalog',
            'items.jobCardService',
            'items.mechanic',
            'payments.receiver',
        ]);

        return view('tenants.sales.show', [
            'sale' => $sale,
        ]);
    }

    public function print(Sale $sale, EnsureSaleInBranchAction $ensureSaleInBranchAction): View
    {
        $sale = $ensureSaleInBranchAction->handle($sale, $this->currentBranchId());

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

    public function edit(Sale $sale, EnsureSaleInBranchAction $ensureSaleInBranchAction): View
    {
        $sale = $ensureSaleInBranchAction->handle($sale, $this->currentBranchId());
        $sale->load('items');

        return view('tenants.sales.edit', array_merge(
            ['sale' => $sale],
            $this->formOptions()
        ));
    }

    public function update(
        SaleRequest $request,
        Sale $sale,
        UpdateSaleAction $action,
        EnsureSaleInBranchAction $ensureSaleInBranchAction
    ): RedirectResponse {
        $sale = $ensureSaleInBranchAction->handle($sale, $this->currentBranchId());
        $action->handle($sale, $request->validated(), $this->currentBranchId());

        return to_route('tenant.sales.index')->with('status', 'Updated.');
    }

    public function destroy(
        Sale $sale,
        DeleteSaleAction $action,
        EnsureSaleInBranchAction $ensureSaleInBranchAction
    ): RedirectResponse {
        $sale = $ensureSaleInBranchAction->handle($sale, $this->currentBranchId());
        $action->handle($sale);

        return to_route('tenant.sales.index')->with('status', 'Deleted.');
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
            'products' => Product::query()->orderBy('name')->get(),
            'serviceCatalogs' => ServiceCatalog::query()->where('branch_id', $branchId)->orderBy('name')->get(),
            'mechanics' => User::query()->where('branch_id', $branchId)->active()->orderBy('name')->get(['id', 'name']),
            'statuses' => SaleStatus::cases(),
            'invoiceTypes' => InvoiceType::cases(),
        ];
    }
}

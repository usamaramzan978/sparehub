<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\JobCard\CreateJobCardAction;
use App\Actions\Tenant\JobCard\DeleteJobCardAction;
use App\Actions\Tenant\JobCard\EnsureJobCardInBranchAction;
use App\Actions\Tenant\JobCard\SyncJobCardLinesAction;
use App\Actions\Tenant\JobCard\UpdateJobCardAction;
use App\Enums\JobCardServiceStatus;
use App\Enums\JobCardStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\JobCardRequest;
use App\Models\Customer;
use App\Models\CustomerVehicle;
use App\Models\InventoryStock;
use App\Models\JobCard;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ServiceCatalog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

final class JobCardController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $perPage = min(max($request->integer('per_page', 15), 5), 100);
        $search = mb_trim($request->string('search')->toString());
        $allowedSortColumns = ['job_no', 'job_date', 'status', 'created_at'];
        [$activeSortBy, $activeSortDirection] = $this->resolveSort($request, $allowedSortColumns);

        $jobCardsQuery = JobCard::query()
            ->with(['customer', 'vehicle', 'assignedEmployee'])
            ->where('branch_id', $branchId)
            ->when(filled($search), function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('job_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('customer', fn (Builder $q) => $q->where('name', 'like', sprintf('%%%s%%', $search)))
                        ->orWhereHas('vehicle', fn (Builder $q) => $q->where('registration_no', 'like', sprintf('%%%s%%', $search)));
                });
            });

        if ($activeSortBy !== null) {
            $jobCardsQuery->orderBy($activeSortBy, $activeSortDirection);
        } else {
            $jobCardsQuery->latest('job_date');
        }

        $jobCards = $jobCardsQuery
            ->paginate($perPage)
            ->withQueryString();

        Customer::query()
            ->where('branch_id', $branchId)
            ->orderBy('name')
            ->get();

        return view('tenants.job-cards.index', [
            'items' => $jobCards,
            'sortBy' => $activeSortBy,
            'sortDirection' => $activeSortDirection,
        ]);
    }

    public function create(): View
    {
        return view('tenants.job-cards.create', $this->formOptions());
    }

    public function store(
        JobCardRequest $request,
        CreateJobCardAction $action,
        SyncJobCardLinesAction $syncJobCardLinesAction
    ): RedirectResponse {
        $validated = $request->validated();
        $jobCard = $action->handle(
            Arr::except($validated, ['services', 'parts']),
            $this->currentBranchId(),
            auth('user')->id()
        );
        $syncJobCardLinesAction->handle($jobCard, $validated);

        return to_route('tenant.job-cards.index')
            ->with('status', 'Created.');
    }

    public function show(JobCard|string $jobCard, EnsureJobCardInBranchAction $ensureJobCardInBranchAction): View
    {
        $jobCard = $this->resolveJobCard($jobCard);
        $jobCard = $ensureJobCardInBranchAction->handle($jobCard, $this->currentBranchId());

        $jobCard->load([
            'customer',
            'vehicle',
            'assignedEmployee',
            'services.serviceCatalog',
            'services.technician',
            'parts.product',
        ]);

        return view('tenants.job-cards.show', [
            'jobCard' => $jobCard,
        ]);
    }

    public function edit(JobCard|string $jobCard, EnsureJobCardInBranchAction $ensureJobCardInBranchAction): View
    {
        $jobCard = $this->resolveJobCard($jobCard);
        $jobCard = $ensureJobCardInBranchAction->handle($jobCard, $this->currentBranchId());

        return view('tenants.job-cards.edit', array_merge(
            ['jobCard' => $jobCard],
            $this->formOptions()
        ));
    }

    public function update(
        JobCardRequest $request,
        JobCard|string $jobCard,
        UpdateJobCardAction $action,
        SyncJobCardLinesAction $syncJobCardLinesAction,
        EnsureJobCardInBranchAction $ensureJobCardInBranchAction
    ): RedirectResponse {
        $jobCard = $this->resolveJobCard($jobCard);
        $jobCard = $ensureJobCardInBranchAction->handle($jobCard, $this->currentBranchId());

        $validated = $request->validated();
        $action->handle($jobCard, Arr::except($validated, ['services', 'parts']), $this->currentBranchId());
        $syncJobCardLinesAction->handle($jobCard, $validated);

        return to_route('tenant.job-cards.index')
            ->with('status', 'Updated.');
    }

    public function destroy(
        JobCard|string $jobCard,
        DeleteJobCardAction $action,
        EnsureJobCardInBranchAction $ensureJobCardInBranchAction
    ): RedirectResponse {
        $jobCard = $this->resolveJobCard($jobCard);
        $jobCard = $ensureJobCardInBranchAction->handle($jobCard, $this->currentBranchId());

        $action->handle($jobCard);

        return to_route('tenant.job-cards.index')
            ->with('status', 'Deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        $branchId = $this->currentBranchId();

        $customers = Customer::query()
            ->where('branch_id', $branchId)
            ->orderBy('name')
            ->get();

        $vehicles = CustomerVehicle::query()
            ->whereIn('customer_id', $customers->pluck('id'))
            ->orderBy('registration_no')
            ->get();

        $employees = User::query()
            ->where('branch_id', $branchId)
            ->orderBy('name')
            ->get();

        $serviceCatalogs = ServiceCatalog::query()
            ->where('branch_id', $branchId)
            ->orderBy('name')
            ->get();

        $products = Product::query()
            ->orderBy('name')
            ->get();

        $productIds = $products->pluck('id')->all();
        $productPriceMap = ProductPrice::query()
            ->withoutGlobalScopes()
            ->whereIn('product_id', $productIds)
            ->orderByRaw('CASE WHEN branch_id = ? THEN 0 WHEN branch_id IS NULL THEN 1 ELSE 2 END', [$branchId])
            ->orderByDesc('effective_from')->latest()
            ->get()
            ->groupBy('product_id')
            ->map(fn ($rows) => $rows->first())
            ->map(fn ($price): array => [
                'cost' => (float) $price->cost,
                'mrp' => (float) $price->mrp,
                'retail_price' => (float) $price->retail_price,
                'wholesale_price' => (float) $price->wholesale_price,
            ])
            ->all();
        $productStockMap = InventoryStock::query()
            ->where('branch_id', $branchId)
            ->whereIn('product_id', $productIds)
            ->selectRaw('product_id, SUM(qty_on_hand) as qty_on_hand')
            ->groupBy('product_id')
            ->pluck('qty_on_hand', 'product_id')
            ->map(fn ($qty): float => (float) $qty)
            ->all();

        return [
            'customers' => $customers,
            'vehicles' => $vehicles,
            'employees' => $employees,
            'serviceCatalogs' => $serviceCatalogs,
            'products' => $products,
            'productPriceMap' => $productPriceMap,
            'productStockMap' => $productStockMap,
            'statuses' => JobCardStatus::cases(),
            'serviceStatuses' => JobCardServiceStatus::cases(),
        ];
    }

    private function resolveJobCard(JobCard|string $jobCard): JobCard
    {
        if ($jobCard instanceof JobCard) {
            return $jobCard;
        }

        return JobCard::query()->findOrFail($jobCard);
    }
}

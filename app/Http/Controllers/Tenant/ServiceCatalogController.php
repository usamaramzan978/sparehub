<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\ServiceCatalog\CreateServiceCatalogAction;
use App\Actions\Tenant\ServiceCatalog\DeleteServiceCatalogAction;
use App\Actions\Tenant\ServiceCatalog\UpdateServiceCatalogAction;
use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\ServiceCatalogRequest;
use App\Models\ServiceCatalog;
use App\Models\Tax;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ServiceCatalogController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $perPage = min(max($request->integer('per_page', 15), 5), 100);
        $search = mb_trim($request->string('search')->toString());
        $sortBy = $request->string('sort_by')->toString();
        $sortDirection = $request->string('sort_direction')->toString();
        $allowedSortColumns = ['code', 'name', 'category', 'base_price', 'duration_minutes', 'status', 'created_at'];
        $activeSortBy = in_array($sortBy, $allowedSortColumns, true) ? $sortBy : null;
        $activeSortDirection = in_array($sortDirection, ['asc', 'desc'], true) ? $sortDirection : 'asc';

        $servicesQuery = ServiceCatalog::query()
            ->with('defaultTax')
            ->where('branch_id', $branchId)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('code', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('category', 'like', sprintf('%%%s%%', $search));
                });
            });

        if ($activeSortBy !== null) {
            $servicesQuery->orderBy($activeSortBy, $activeSortDirection);
        } else {
            $servicesQuery->latest();
        }

        $services = $servicesQuery
            ->paginate($perPage)
            ->withQueryString();

        return view('tenants.service-catalog.index', [
            'items' => $services,
            'sortBy' => $activeSortBy,
            'sortDirection' => $activeSortDirection,
        ]);
    }

    public function create(): View
    {
        $taxes = Tax::query()
            ->where('status', RecordStatus::ACTIVE->value)
            ->orderBy('name')
            ->get();

        return view('tenants.service-catalog.create', [
            'taxes' => $taxes,
            'statuses' => RecordStatus::cases(),
        ]);
    }

    public function store(ServiceCatalogRequest $request, CreateServiceCatalogAction $action): RedirectResponse
    {
        $action->handle($request->validated(), $this->currentBranchId());

        return to_route('tenant.service-catalog.index')
            ->with('status', 'Created.');
    }

    public function show(ServiceCatalog $serviceCatalog): View
    {
        $this->ensureServiceInCurrentBranch($serviceCatalog);

        $serviceCatalog->load(['branch', 'defaultTax']);

        return view('tenants.service-catalog.show', [
            'serviceCatalog' => $serviceCatalog,
        ]);
    }

    public function edit(ServiceCatalog $serviceCatalog): View
    {
        $this->ensureServiceInCurrentBranch($serviceCatalog);

        $taxes = Tax::query()
            ->where('status', RecordStatus::ACTIVE->value)
            ->orderBy('name')
            ->get();

        return view('tenants.service-catalog.edit', [
            'serviceCatalog' => $serviceCatalog,
            'taxes' => $taxes,
            'statuses' => RecordStatus::cases(),
        ]);
    }

    public function update(
        ServiceCatalogRequest $request,
        ServiceCatalog $serviceCatalog,
        UpdateServiceCatalogAction $action
    ): RedirectResponse {
        $this->ensureServiceInCurrentBranch($serviceCatalog);

        $action->handle($serviceCatalog, $request->validated(), $this->currentBranchId());

        return to_route('tenant.service-catalog.index')
            ->with('status', 'Updated.');
    }

    public function destroy(ServiceCatalog $serviceCatalog, DeleteServiceCatalogAction $action): RedirectResponse
    {
        $this->ensureServiceInCurrentBranch($serviceCatalog);

        $action->handle($serviceCatalog);

        return to_route('tenant.service-catalog.index')
            ->with('status', 'Deleted.');
    }

    private function ensureServiceInCurrentBranch(ServiceCatalog $serviceCatalog): void
    {
        abort_if($serviceCatalog->branch_id !== $this->currentBranchId(), 404);
    }
}

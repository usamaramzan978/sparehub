<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\ServiceCatalogRequest;
use App\Models\ServiceCatalog;
use App\Models\Tax;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ServiceCatalogController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $perPage = min(max($request->integer('per_page', 15), 5), 100);

        $services = ServiceCatalog::query()
            ->with('defaultTax')
            ->where('branch_id', $branchId)
            ->latest()
            ->paginate($perPage);

        return view('tenants.service-catalog.index', [
            'items' => $services,
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

    public function store(ServiceCatalogRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $payload['branch_id'] = $this->currentBranchId();

        ServiceCatalog::query()->create($payload);

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

    public function update(ServiceCatalogRequest $request, ServiceCatalog $serviceCatalog): RedirectResponse
    {
        $this->ensureServiceInCurrentBranch($serviceCatalog);

        $payload = $request->validated();
        $payload['branch_id'] = $this->currentBranchId();

        $serviceCatalog->update($payload);

        return to_route('tenant.service-catalog.index')
            ->with('status', 'Updated.');
    }

    public function destroy(ServiceCatalog $serviceCatalog): RedirectResponse
    {
        $this->ensureServiceInCurrentBranch($serviceCatalog);

        $serviceCatalog->delete();

        return to_route('tenant.service-catalog.index')
            ->with('status', 'Deleted.');
    }

    private function ensureServiceInCurrentBranch(ServiceCatalog $serviceCatalog): void
    {
        abort_if($serviceCatalog->branch_id !== $this->currentBranchId(), 404);
    }
}

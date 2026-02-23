<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\JobCardService\CreateJobCardServiceAction;
use App\Actions\Tenant\JobCardService\DeleteJobCardServiceAction;
use App\Actions\Tenant\JobCardService\EnsureJobCardServiceInBranchAction;
use App\Actions\Tenant\JobCardService\UpdateJobCardServiceAction;
use App\Enums\JobCardServiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\JobCardServiceRequest;
use App\Models\JobCard;
use App\Models\JobCardService;
use App\Models\ServiceCatalog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class JobCardServiceController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $perPage = min(max($request->integer('per_page', 15), 5), 100);

        $services = JobCardService::query()
            ->with(['jobCard.customer', 'serviceCatalog', 'technician'])
            ->whereHas('jobCard', fn ($query) => $query->where('branch_id', $branchId))
            ->latest()
            ->paginate($perPage);

        return view('tenants.job-card-services.index', [
            'items' => $services,
        ]);
    }

    public function create(): View
    {
        return view('tenants.job-card-services.create', $this->formOptions());
    }

    public function store(JobCardServiceRequest $request, CreateJobCardServiceAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('tenant.job-card-services.index')
            ->with('status', 'Created.');
    }

    public function show(
        JobCardService $jobCardService,
        EnsureJobCardServiceInBranchAction $ensureJobCardServiceInBranchAction
    ): View {
        $jobCardService = $ensureJobCardServiceInBranchAction->handle($jobCardService, $this->currentBranchId());

        $jobCardService->load(['jobCard.customer', 'jobCard.vehicle', 'serviceCatalog', 'technician']);

        return view('tenants.job-card-services.show', [
            'serviceLine' => $jobCardService,
        ]);
    }

    public function edit(
        JobCardService $jobCardService,
        EnsureJobCardServiceInBranchAction $ensureJobCardServiceInBranchAction
    ): View {
        $jobCardService = $ensureJobCardServiceInBranchAction->handle($jobCardService, $this->currentBranchId());

        return view('tenants.job-card-services.edit', array_merge(
            ['serviceLine' => $jobCardService],
            $this->formOptions()
        ));
    }

    public function update(
        JobCardServiceRequest $request,
        JobCardService $jobCardService,
        UpdateJobCardServiceAction $action,
        EnsureJobCardServiceInBranchAction $ensureJobCardServiceInBranchAction
    ): RedirectResponse {
        $jobCardService = $ensureJobCardServiceInBranchAction->handle($jobCardService, $this->currentBranchId());
        $action->handle($jobCardService, $request->validated());

        return to_route('tenant.job-card-services.index')
            ->with('status', 'Updated.');
    }

    public function destroy(
        JobCardService $jobCardService,
        DeleteJobCardServiceAction $action,
        EnsureJobCardServiceInBranchAction $ensureJobCardServiceInBranchAction
    ): RedirectResponse {
        $jobCardService = $ensureJobCardServiceInBranchAction->handle($jobCardService, $this->currentBranchId());
        $action->handle($jobCardService);

        return to_route('tenant.job-card-services.index')
            ->with('status', 'Deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        $branchId = $this->currentBranchId();

        $jobCards = JobCard::query()
            ->where('branch_id', $branchId)
            ->latest('job_date')
            ->get();

        $serviceCatalogs = ServiceCatalog::query()
            ->where('branch_id', $branchId)
            ->orderBy('name')
            ->get();

        $technicians = User::query()
            ->where('branch_id', $branchId)
            ->orderBy('name')
            ->get();

        return [
            'jobCards' => $jobCards,
            'serviceCatalogs' => $serviceCatalogs,
            'technicians' => $technicians,
            'statuses' => JobCardServiceStatus::cases(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\JobCardPart\CreateJobCardPartAction;
use App\Actions\Tenant\JobCardPart\DeleteJobCardPartAction;
use App\Actions\Tenant\JobCardPart\EnsureJobCardPartInBranchAction;
use App\Actions\Tenant\JobCardPart\UpdateJobCardPartAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\JobCardPartRequest;
use App\Models\JobCard;
use App\Models\JobCardPart;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class JobCardPartController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $perPage = min(max($request->integer('per_page', 15), 5), 100);
        $search = mb_trim($request->string('search')->toString());

        $parts = JobCardPart::query()
            ->with(['jobCard.customer', 'product'])
            ->whereHas('jobCard', fn ($query) => $query->where('branch_id', $branchId))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->whereHas('jobCard', fn (Builder $q) => $q->where('job_no', 'like', sprintf('%%%s%%', $search)))
                        ->orWhereHas('product', fn (Builder $q) => $q->where('name', 'like', sprintf('%%%s%%', $search)));
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('tenants.job-card-parts.index', [
            'items' => $parts,
        ]);
    }

    public function create(): View
    {
        return view('tenants.job-card-parts.create', $this->formOptions());
    }

    public function store(JobCardPartRequest $request, CreateJobCardPartAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('tenant.job-card-parts.index')
            ->with('status', 'Created.');
    }

    public function show(JobCardPart $jobCardPart, EnsureJobCardPartInBranchAction $ensureJobCardPartInBranchAction): View
    {
        $jobCardPart = $ensureJobCardPartInBranchAction->handle($jobCardPart, $this->currentBranchId());

        $jobCardPart->load(['jobCard.customer', 'jobCard.vehicle', 'product']);

        return view('tenants.job-card-parts.show', [
            'partLine' => $jobCardPart,
        ]);
    }

    public function edit(JobCardPart $jobCardPart, EnsureJobCardPartInBranchAction $ensureJobCardPartInBranchAction): View
    {
        $jobCardPart = $ensureJobCardPartInBranchAction->handle($jobCardPart, $this->currentBranchId());

        return view('tenants.job-card-parts.edit', array_merge(
            ['partLine' => $jobCardPart],
            $this->formOptions()
        ));
    }

    public function update(
        JobCardPartRequest $request,
        JobCardPart $jobCardPart,
        UpdateJobCardPartAction $action,
        EnsureJobCardPartInBranchAction $ensureJobCardPartInBranchAction
    ): RedirectResponse {
        $jobCardPart = $ensureJobCardPartInBranchAction->handle($jobCardPart, $this->currentBranchId());
        $action->handle($jobCardPart, $request->validated());

        return to_route('tenant.job-card-parts.index')
            ->with('status', 'Updated.');
    }

    public function destroy(
        JobCardPart $jobCardPart,
        DeleteJobCardPartAction $action,
        EnsureJobCardPartInBranchAction $ensureJobCardPartInBranchAction
    ): RedirectResponse {
        $jobCardPart = $ensureJobCardPartInBranchAction->handle($jobCardPart, $this->currentBranchId());
        $action->handle($jobCardPart);

        return to_route('tenant.job-card-parts.index')
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

        $products = Product::query()
            ->orderBy('name')
            ->get();

        return [
            'jobCards' => $jobCards,
            'products' => $products,
        ];
    }
}

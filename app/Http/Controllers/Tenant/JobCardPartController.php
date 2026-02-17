<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\JobCardPartRequest;
use App\Models\JobCard;
use App\Models\JobCardPart;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class JobCardPartController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $perPage = min(max($request->integer('per_page', 15), 5), 100);

        $parts = JobCardPart::query()
            ->with(['jobCard.customer', 'product'])
            ->whereHas('jobCard', fn ($query) => $query->where('branch_id', $branchId))
            ->latest()
            ->paginate($perPage);

        JobCard::query()
            ->where('branch_id', $branchId)
            ->latest('job_date')
            ->get();

        Product::query()
            ->orderBy('name')
            ->get();

        return view('tenants.job-card-parts.index', [
            'items' => $parts,
        ]);
    }

    public function create(): View
    {
        return view('tenants.job-card-parts.create', $this->formOptions());
    }

    public function store(JobCardPartRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $payload['line_total'] = (float) $payload['qty'] * (float) $payload['unit_price'];

        JobCardPart::query()->create($payload);

        return to_route('tenant.job-card-parts.index')
            ->with('status', 'Created.');
    }

    public function show(JobCardPart $jobCardPart): View
    {
        $this->ensureJobCardPartInCurrentBranch($jobCardPart);

        $jobCardPart->load(['jobCard.customer', 'jobCard.vehicle', 'product']);

        return view('tenants.job-card-parts.show', [
            'partLine' => $jobCardPart,
        ]);
    }

    public function edit(JobCardPart $jobCardPart): View
    {
        $this->ensureJobCardPartInCurrentBranch($jobCardPart);

        return view('tenants.job-card-parts.edit', array_merge(
            ['partLine' => $jobCardPart],
            $this->formOptions()
        ));
    }

    public function update(JobCardPartRequest $request, JobCardPart $jobCardPart): RedirectResponse
    {
        $this->ensureJobCardPartInCurrentBranch($jobCardPart);

        $payload = $request->validated();
        $payload['line_total'] = (float) $payload['qty'] * (float) $payload['unit_price'];

        $jobCardPart->update($payload);

        return to_route('tenant.job-card-parts.index')
            ->with('status', 'Updated.');
    }

    public function destroy(JobCardPart $jobCardPart): RedirectResponse
    {
        $this->ensureJobCardPartInCurrentBranch($jobCardPart);

        $jobCardPart->delete();

        return to_route('tenant.job-card-parts.index')
            ->with('status', 'Deleted.');
    }

    private function ensureJobCardPartInCurrentBranch(JobCardPart $jobCardPart): void
    {
        $jobCard = $jobCardPart->jobCard;

        abort_if(! $jobCard instanceof JobCard || $jobCard->branch_id !== $this->currentBranchId(), 404);
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

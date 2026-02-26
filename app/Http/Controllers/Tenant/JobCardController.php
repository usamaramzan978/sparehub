<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\JobCard\CreateJobCardAction;
use App\Actions\Tenant\JobCard\DeleteJobCardAction;
use App\Actions\Tenant\JobCard\EnsureJobCardInBranchAction;
use App\Actions\Tenant\JobCard\UpdateJobCardAction;
use App\Enums\JobCardStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\JobCardRequest;
use App\Models\Customer;
use App\Models\CustomerVehicle;
use App\Models\JobCard;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

    public function store(JobCardRequest $request, CreateJobCardAction $action): RedirectResponse
    {
        $action->handle($request->validated(), $this->currentBranchId(), auth('user')->id());

        return to_route('tenant.job-cards.index')
            ->with('status', 'Created.');
    }

    public function show(JobCard $jobCard, EnsureJobCardInBranchAction $ensureJobCardInBranchAction): View
    {
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

    public function edit(JobCard $jobCard, EnsureJobCardInBranchAction $ensureJobCardInBranchAction): View
    {
        $jobCard = $ensureJobCardInBranchAction->handle($jobCard, $this->currentBranchId());

        return view('tenants.job-cards.edit', array_merge(
            ['jobCard' => $jobCard],
            $this->formOptions()
        ));
    }

    public function update(
        JobCardRequest $request,
        JobCard $jobCard,
        UpdateJobCardAction $action,
        EnsureJobCardInBranchAction $ensureJobCardInBranchAction
    ): RedirectResponse {
        $jobCard = $ensureJobCardInBranchAction->handle($jobCard, $this->currentBranchId());
        $action->handle($jobCard, $request->validated(), $this->currentBranchId());

        return to_route('tenant.job-cards.index')
            ->with('status', 'Updated.');
    }

    public function destroy(
        JobCard $jobCard,
        DeleteJobCardAction $action,
        EnsureJobCardInBranchAction $ensureJobCardInBranchAction
    ): RedirectResponse {
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

        return [
            'customers' => $customers,
            'vehicles' => $vehicles,
            'employees' => $employees,
            'statuses' => JobCardStatus::cases(),
        ];
    }
}

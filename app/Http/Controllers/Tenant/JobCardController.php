<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

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

        $jobCards = JobCard::query()
            ->with(['customer', 'vehicle', 'assignedEmployee'])
            ->where('branch_id', $branchId)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('job_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('customer', fn (Builder $q) => $q->where('name', 'like', sprintf('%%%s%%', $search)))
                        ->orWhereHas('vehicle', fn (Builder $q) => $q->where('registration_no', 'like', sprintf('%%%s%%', $search)));
                });
            })
            ->latest('job_date')
            ->paginate($perPage)
            ->withQueryString();

        $customers = Customer::query()
            ->where('branch_id', $branchId)
            ->orderBy('name')
            ->get();

        CustomerVehicle::query()
            ->whereIn('customer_id', $customers->pluck('id'))
            ->orderBy('registration_no')
            ->get();

        User::query()
            ->where('branch_id', $branchId)
            ->orderBy('name')
            ->get();

        return view('tenants.job-cards.index', [
            'items' => $jobCards,
        ]);
    }

    public function create(): View
    {
        return view('tenants.job-cards.create', $this->formOptions());
    }

    public function store(JobCardRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $payload['branch_id'] = $this->currentBranchId();
        $payload['created_by'] = auth('user')->id();

        JobCard::query()->create($payload);

        return to_route('tenant.job-cards.index')
            ->with('status', 'Created.');
    }

    public function show(JobCard $jobCard): View
    {
        $this->ensureJobCardInCurrentBranch($jobCard);

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

    public function edit(JobCard $jobCard): View
    {
        $this->ensureJobCardInCurrentBranch($jobCard);

        return view('tenants.job-cards.edit', array_merge(
            ['jobCard' => $jobCard],
            $this->formOptions()
        ));
    }

    public function update(JobCardRequest $request, JobCard $jobCard): RedirectResponse
    {
        $this->ensureJobCardInCurrentBranch($jobCard);

        $payload = $request->validated();
        $payload['branch_id'] = $this->currentBranchId();

        $jobCard->update($payload);

        return to_route('tenant.job-cards.index')
            ->with('status', 'Updated.');
    }

    public function destroy(JobCard $jobCard): RedirectResponse
    {
        $this->ensureJobCardInCurrentBranch($jobCard);

        $jobCard->delete();

        return to_route('tenant.job-cards.index')
            ->with('status', 'Deleted.');
    }

    private function ensureJobCardInCurrentBranch(JobCard $jobCard): void
    {
        abort_if($jobCard->branch_id !== $this->currentBranchId(), 404);
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

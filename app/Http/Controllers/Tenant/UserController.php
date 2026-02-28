<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\User\CreateUserAction;
use App\Actions\Tenant\User\DeleteUserAction;
use App\Actions\Tenant\User\SyncUserCommissionRulesAction;
use App\Actions\Tenant\User\UpdateUserAction;
use App\Enums\ServiceCatalogType;
use App\Enums\UserDeletionResult;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UserRequest;
use App\Models\Branch;
use App\Models\SaleItem;
use App\Models\ServiceCatalog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

final class UserController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $perPage = min(max($request->integer('per_page', 15), 5), 100);
        $search = mb_trim($request->string('search')->toString());
        $status = $request->string('status')->toString();
        $allowedStatuses = Arr::map(UserStatus::cases(), fn (UserStatus $item): string => $item->value);
        $allowedSortColumns = ['name', 'email', 'phone', 'status', 'created_at'];
        [$activeSortBy, $activeSortDirection] = $this->resolveSort($request, $allowedSortColumns);

        $usersQuery = User::query()
            ->with('branch')
            ->where('branch_id', $branchId)
            ->when(filled($search), function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('email', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('phone', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('cnic', 'like', sprintf('%%%s%%', $search));
                });
            })
            ->when(in_array($status, $allowedStatuses, true), fn (Builder $query): Builder => $query->where('status', $status));

        if ($activeSortBy !== null) {
            $usersQuery->orderBy($activeSortBy, $activeSortDirection);
        } else {
            $usersQuery->latest();
        }

        $users = $usersQuery
            ->paginate($perPage)
            ->withQueryString();

        return view('tenants.users.index', [
            'items' => $users,
            'statuses' => UserStatus::cases(),
            'sortBy' => $activeSortBy,
            'sortDirection' => $activeSortDirection,
        ]);
    }

    public function create(): View
    {
        $statuses = UserStatus::cases();
        $branch = Branch::query()->find($this->currentBranchId());

        return view('tenants.users.create', [
            'statuses' => $statuses,
            'branch' => $branch,
            'labourServices' => $this->labourServices(),
        ]);
    }

    public function show(User|string $user): View
    {
        $user = $this->resolveUser($user);
        $this->ensureUserInCurrentBranch($user);
        $user->load(['branch', 'commissionRules.serviceCatalog']);

        $serviceSales = SaleItem::query()
            ->with(['sale:id,invoice_no,invoice_date,created_by', 'serviceCatalog:id,name'])
            ->whereHas('sale', fn (Builder $query): Builder => $query->where('created_by', $user->id))
            ->where('line_type', 'service')
            ->latest('created_at')
            ->limit(100)
            ->get();

        $commissionSummary = [
            'rules_count' => $user->commissionRules->count(),
            'total_payable' => (float) $user->commissionRules->sum('payable_amount'),
            'service_entries_count' => $serviceSales->count(),
            'service_entries_total' => (float) $serviceSales->sum('line_total'),
        ];

        return view('tenants.users.show', [
            'user' => $user,
            'serviceSales' => $serviceSales,
            'commissionSummary' => $commissionSummary,
        ]);
    }

    public function store(
        UserRequest $request,
        CreateUserAction $action,
        SyncUserCommissionRulesAction $syncUserCommissionRulesAction
    ): RedirectResponse {
        $action->handle($request->validated(), $this->currentBranchId(), $syncUserCommissionRulesAction);

        return to_route('tenant.users.index')
            ->with('status', 'Created.');
    }

    public function edit(User|string $user): View
    {
        $user = $this->resolveUser($user);
        $this->ensureUserInCurrentBranch($user);

        $statuses = UserStatus::cases();
        $branch = Branch::query()->find($this->currentBranchId());

        return view('tenants.users.edit', [
            'user' => $user,
            'statuses' => $statuses,
            'branch' => $branch,
            'labourServices' => $this->labourServices(),
        ]);
    }

    public function update(
        UserRequest $request,
        User|string $user,
        UpdateUserAction $action,
        SyncUserCommissionRulesAction $syncUserCommissionRulesAction
    ): RedirectResponse {
        $user = $this->resolveUser($user);
        $this->ensureUserInCurrentBranch($user);

        $action->handle($user, $request->validated(), $this->currentBranchId(), $syncUserCommissionRulesAction);

        return to_route('tenant.users.index')
            ->with('status', 'Updated.');
    }

    public function destroy(User|string $user, DeleteUserAction $action): RedirectResponse
    {
        $user = $this->resolveUser($user);
        $this->ensureUserInCurrentBranch($user);

        return match ($action->handle($user)) {
            UserDeletionResult::LastTenantOwner => to_route('tenant.users.index')
                ->with('error', 'At least one tenant owner must remain.'),
            UserDeletionResult::Deleted => to_route('tenant.users.index')
                ->with('status', 'Deleted.'),
        };
    }

    private function resolveUser(User|string $user): User
    {
        if ($user instanceof User) {
            return $user;
        }

        return User::query()->findOrFail($user);
    }

    /**
     * @return Collection<int, ServiceCatalog>
     */
    private function labourServices(): Collection
    {
        return ServiceCatalog::query()
            ->where('branch_id', $this->currentBranchId())
            ->where('type', ServiceCatalogType::Labour->value)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }
}

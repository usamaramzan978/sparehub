<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\Branch\CreateBranchAction;
use App\Actions\Tenant\Branch\DeleteBranchAction;
use App\Actions\Tenant\Branch\UpdateBranchAction;
use App\Enums\BranchDeletionResult;
use App\Enums\BranchStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\BranchRequest;
use App\Models\Branch;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class BranchController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = $request->integer('per_page', 15);
        $perPage = min(max($perPage, 5), 100);

        $search = mb_trim($request->string('search')->toString());
        $allowedSortColumns = ['code', 'name', 'status', 'created_at'];
        [$activeSortBy, $activeSortDirection] = $this->resolveSort($request, $allowedSortColumns);

        $branchesQuery = Branch::query()
            ->when(filled($search), function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('code', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('name', 'like', sprintf('%%%s%%', $search));
                });
            });

        if ($activeSortBy !== null) {
            $branchesQuery->orderBy($activeSortBy, $activeSortDirection);
        } else {
            $branchesQuery->latest();
        }

        $branches = $branchesQuery
            ->paginate($perPage)
            ->withQueryString();

        $canDeleteBranch = Branch::query()->count() > 1;

        return view('tenants.branches.index', [
            'items' => $branches,
            'canDeleteBranch' => $canDeleteBranch,
            'sortBy' => $activeSortBy,
            'sortDirection' => $activeSortDirection,
        ]);
    }

    public function store(BranchRequest $request, CreateBranchAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('tenant.branches.index')
            ->with('status', 'Created.');
    }

    public function create(): View
    {
        $statuses = BranchStatus::cases();

        return view('tenants.branches.create', [
            'statuses' => $statuses,
        ]);
    }

    public function show(string $tenant): View
    {
        unset($tenant);

        $branch = $this->resolveBranchFromRoute();

        return view('tenants.branches.show', ['branch' => $branch]);
    }

    public function update(BranchRequest $request, string $tenant, UpdateBranchAction $action): RedirectResponse
    {
        unset($tenant);

        $branch = $this->resolveBranchFromRoute();

        $action->handle($branch, $request->validated());

        return to_route('tenant.branches.index')
            ->with('status', 'Updated.');
    }

    public function edit(string $tenant): View
    {
        unset($tenant);

        $branch = $this->resolveBranchFromRoute();

        $statuses = BranchStatus::cases();

        return view('tenants.branches.edit', [
            'branch' => $branch,
            'statuses' => $statuses,
        ]);
    }

    public function destroy(string $tenant, DeleteBranchAction $action): RedirectResponse
    {
        unset($tenant);

        $branch = $this->resolveBranchFromRoute();

        return match ($action->handle($branch, session('tenant.current_branch_id'))) {
            BranchDeletionResult::LastRemaining => to_route('tenant.branches.index')
                ->with('error', 'At least one branch must remain.'),
            BranchDeletionResult::CurrentSelected => to_route('tenant.branches.index')
                ->with('error', 'You cannot delete the currently selected branch.'),
            BranchDeletionResult::Deleted => to_route('tenant.branches.index')
                ->with('status', 'Deleted.'),
        };
    }

    private function resolveBranchFromRoute(): Branch
    {
        $branchRouteParameter = request()->route('branch');

        if ($branchRouteParameter instanceof Branch) {
            return $branchRouteParameter;
        }

        return Branch::query()->findOrFail((string) $branchRouteParameter);
    }
}

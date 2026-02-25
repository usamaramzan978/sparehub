<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\User\CreateUserAction;
use App\Actions\Tenant\User\DeleteUserAction;
use App\Actions\Tenant\User\UpdateUserAction;
use App\Enums\UserDeletionResult;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UserRequest;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

final class UserController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $perPage = min(max($request->integer('per_page', 15), 5), 100);
        $search = mb_trim($request->string('search')->toString());
        $status = $request->string('status')->toString();
        $allowedStatuses = Arr::map(UserStatus::cases(), fn (UserStatus $item): string => $item->value);

        $users = User::query()
            ->with('branch')
            ->where('branch_id', $branchId)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('email', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('phone', 'like', sprintf('%%%s%%', $search));
                });
            })
            ->when(in_array($status, $allowedStatuses, true), fn (Builder $query) => $query->where('status', $status))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('tenants.users.index', [
            'items' => $users,
            'statuses' => UserStatus::cases(),
        ]);
    }

    public function create(): View
    {
        $statuses = UserStatus::cases();
        $branch = Branch::query()->find($this->currentBranchId());

        return view('tenants.users.create', [
            'statuses' => $statuses,
            'branch' => $branch,
        ]);
    }

    public function show(string $tenant, User $user): View
    {
        unset($tenant);

        $this->ensureUserInCurrentBranch($user);
        $user->load('branch');

        return view('tenants.users.show', ['user' => $user]);
    }

    public function store(UserRequest $request, CreateUserAction $action): RedirectResponse
    {
        $action->handle($request->validated(), $this->currentBranchId());

        return to_route('tenant.users.index')
            ->with('status', 'Created.');
    }

    public function edit(string $tenant, User $user): View
    {
        unset($tenant);

        $this->ensureUserInCurrentBranch($user);

        $statuses = UserStatus::cases();
        $branch = Branch::query()->find($this->currentBranchId());

        return view('tenants.users.edit', [
            'user' => $user,
            'statuses' => $statuses,
            'branch' => $branch,
        ]);
    }

    public function update(string $tenant, UserRequest $request, User $user, UpdateUserAction $action): RedirectResponse
    {
        unset($tenant);

        $this->ensureUserInCurrentBranch($user);

        $action->handle($user, $request->validated(), $this->currentBranchId());

        return to_route('tenant.users.index')
            ->with('status', 'Updated.');
    }

    public function destroy(string $tenant, User $user, DeleteUserAction $action): RedirectResponse
    {
        unset($tenant);

        $this->ensureUserInCurrentBranch($user);

        return match ($action->handle($user)) {
            UserDeletionResult::LastTenantOwner => to_route('tenant.users.index')
                ->with('error', 'At least one tenant owner must remain.'),
            UserDeletionResult::Deleted => to_route('tenant.users.index')
                ->with('status', 'Deleted.'),
        };
    }
}

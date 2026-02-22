<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\LoginUserType;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UserRequest;
use App\Models\Branch;
use App\Models\LoginMap;
use App\Models\User;
use App\Support\AuditTimelineLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;

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

    public function show(User $user): View
    {
        $this->ensureUserInCurrentBranch($user);
        $user->load('branch');

        return view('tenants.users.show', ['user' => $user]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['branch_id'] = $this->currentBranchId();
        $data['email'] = mb_strtolower((string) $data['email']);
        $data['password'] = Hash::make($data['password']);

        $user = User::query()->create($data);
        $this->syncLoginMap($user);

        AuditTimelineLogger::log(
            event: 'user_created',
            description: 'Tenant user created.',
            causer: Auth::guard('user')->user(),
            subject: $user,
            properties: [
                'user_id' => (string) $user->id,
                'user_email' => $user->email,
                'branch_id' => (string) $user->branch_id,
            ],
        );

        return to_route('tenant.users.index')
            ->with('status', 'Created.');
    }

    public function edit(User $user): View
    {
        $this->ensureUserInCurrentBranch($user);

        $statuses = UserStatus::cases();
        $branch = Branch::query()->find($this->currentBranchId());

        return view('tenants.users.edit', [
            'user' => $user,
            'statuses' => $statuses,
            'branch' => $branch,
        ]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $this->ensureUserInCurrentBranch($user);

        $data = $request->validated();
        $data['branch_id'] = $this->currentBranchId();
        $data['email'] = mb_strtolower((string) $data['email']);

        if (empty($data['password'])) {
            $data = Arr::except($data, ['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);
        $this->syncLoginMap($user->refresh());

        AuditTimelineLogger::log(
            event: 'user_updated',
            description: 'Tenant user updated.',
            causer: Auth::guard('user')->user(),
            subject: $user,
            properties: [
                'user_id' => (string) $user->id,
                'user_email' => $user->email,
                'changed_attributes' => array_keys($data),
            ],
        );

        return to_route('tenant.users.index')
            ->with('status', 'Updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->ensureUserInCurrentBranch($user);

        $userSnapshot = [
            'user_id' => (string) $user->id,
            'user_email' => $user->email,
            'branch_id' => (string) $user->branch_id,
        ];

        $this->deleteLoginMap($user);
        $user->delete();

        AuditTimelineLogger::log(
            event: 'user_deleted',
            description: 'Tenant user deleted.',
            causer: Auth::guard('user')->user(),
            subject: $user,
            properties: $userSnapshot,
        );

        return to_route('tenant.users.index')
            ->with('status', 'Deleted.');
    }

    private function syncLoginMap(User $user): void
    {
        $tenantId = (string) tenant('id');
        $userStatus = $user->status instanceof UserStatus ? $user->status->value : (string) $user->status;

        throw_if($tenantId === '', HttpException::class, 422, 'Invalid tenant context.');

        LoginMap::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'type' => LoginUserType::USER->value,
                'type_id' => $user->id,
            ],
            [
                'email' => mb_strtolower($user->email),
                'password' => $user->password,
                'status' => $userStatus === UserStatus::ACTIVE->value,
            ]
        );
    }

    private function deleteLoginMap(User $user): void
    {
        $tenantId = (string) tenant('id');

        if ($tenantId === '') {
            return;
        }

        LoginMap::query()
            ->where('tenant_id', $tenantId)
            ->where('type', LoginUserType::USER->value)
            ->where('type_id', $user->id)
            ->delete();
    }
}

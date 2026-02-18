<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\RoleRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class RoleController extends Controller
{
    public function index(Request $request): View
    {
        $roles = Role::query()
            ->with('permissions')
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return view('tenants.roles.index', ['items' => $roles]);
    }

    public function create(): View
    {
        $permissions = Permission::query()->orderBy('name')->get();

        return view('tenants.roles.create', [
            'permissions' => $permissions,
        ]);
    }

    public function store(RoleRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $permissions = $payload['permissions'] ?? [];
        unset($payload['permissions']);

        $role = Role::query()->create($payload);
        $role->syncPermissions($permissions);

        return to_route('tenant.roles.index')
            ->with('status', 'Created.');
    }

    public function show(Role $role): View
    {
        $role->load('permissions');

        return view('tenants.roles.show', ['role' => $role]);
    }

    public function edit(Role $role): View
    {
        $permissions = Permission::query()->orderBy('name')->get();
        $role->load('permissions');

        return view('tenants.roles.edit', [
            'role' => $role,
            'permissions' => $permissions,
        ]);
    }

    public function update(RoleRequest $request, Role $role): RedirectResponse
    {
        Gate::authorize('update', $role);

        $payload = $request->validated();
        $permissions = $payload['permissions'] ?? [];
        unset($payload['permissions']);

        $role->update($payload);
        $role->syncPermissions($permissions);

        return to_route('tenant.roles.index')
            ->with('status', 'Updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        Gate::authorize('delete', $role);

        $role->delete();

        return to_route('tenant.roles.index')
            ->with('status', 'Deleted.');
    }
}

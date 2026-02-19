<?php

declare(strict_types=1);

use App\Enums\BranchStatus;
use App\Enums\UserStatus;
use App\Http\Controllers\Tenant\RoleController;
use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Stancl\Tenancy\Middleware\InitializeTenancyByPath;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

beforeEach(function (): void {
    Config::set('database.connections.tenant', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]);
    Config::set('database.default', 'tenant');

    Artisan::call('migrate:fresh', [
        '--database' => 'tenant',
        '--path' => database_path('migrations/tenant'),
        '--realpath' => true,
        '--force' => true,
    ]);

    $this->withoutMiddleware([
        InitializeTenancyByPath::class,
        PreventAccessFromCentralDomains::class,
    ]);

    URL::defaults(['tenant' => 'test-tenant-id']);
});

function rolesTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

function authenticateRolesModuleUser(): User
{
    $branch = Branch::query()->create([
        'code' => 'MAIN',
        'name' => 'Main Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $user = User::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Roles Admin',
        'email' => 'roles.admin+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $branch->id]);

    return $user;
}

it('shows roles index', function (): void {
    authenticateRolesModuleUser();

    Role::query()->create(['name' => 'Cashier', 'guard_name' => 'web']);

    $response = $this->get(rolesTenantRoute('roles.index'));

    $response->assertSuccessful();
    $response->assertSee('Cashier');
});

it('stores role with assigned permissions', function (): void {
    authenticateRolesModuleUser();

    $permission = Permission::query()->create([
        'name' => 'sales.view',
        'guard_name' => 'web',
    ]);

    $response = $this->post(rolesTenantRoute('roles.store'), [
        'name' => 'Manager',
        'guard_name' => 'web',
        'permissions' => [$permission->id],
    ]);

    $response->assertRedirect(rolesTenantRoute('roles.index'));

    $role = Role::query()->where('name', 'Manager')->firstOrFail();
    expect($role->permissions->pluck('id')->all())->toContain($permission->id);
});

it('validates role required fields', function (): void {
    authenticateRolesModuleUser();

    $response = $this->from(rolesTenantRoute('roles.create'))
        ->post(rolesTenantRoute('roles.store'), [
            'name' => '',
            'guard_name' => '',
        ]);

    $response->assertRedirect(rolesTenantRoute('roles.create'));
    $response->assertSessionHasErrors(['name', 'guard_name']);
});

it('shows role details', function (): void {
    authenticateRolesModuleUser();

    $role = Role::query()->create(['name' => 'Viewer', 'guard_name' => 'web']);

    $response = (new RoleController())->show($role);

    expect($response->name())->toBe('tenants.roles.show');
    expect($response->getData()['role']->name)->toBe('Viewer');
});

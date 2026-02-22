<?php

declare(strict_types=1);

use App\Enums\RoleName;
use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\TenantRolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
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

    $this->seed(TenantRolePermissionSeeder::class);
});

it('provisions baseline permissions for sales purchases settings and users modules', function (): void {
    $requiredPermissions = [
        'sales.view',
        'sales.create',
        'sales.update',
        'sales.delete',
        'purchases.view',
        'purchases.create',
        'purchases.update',
        'purchases.delete',
        'settings.view',
        'settings.update',
        'users.view',
        'users.create',
        'users.update',
        'users.delete',
    ];

    foreach ($requiredPermissions as $permissionName) {
        expect(Permission::query()->where('name', $permissionName)->exists())
            ->toBeTrue("Missing permission: {$permissionName}");
    }
});

it('keeps tenant owner and admin fully privileged while manager stays non-delete', function (): void {
    $owner = Role::query()->where('name', RoleName::TENANT_OWNER->value)->firstOrFail();
    $admin = Role::query()->where('name', RoleName::ADMIN->value)->firstOrFail();
    $manager = Role::query()->where('name', RoleName::MANAGER->value)->firstOrFail();

    $allPermissionNames = Permission::query()->pluck('name')->all();

    expect($owner->permissions->pluck('name')->sort()->values()->all())
        ->toBe(collect($allPermissionNames)->sort()->values()->all());
    expect($admin->permissions->pluck('name')->sort()->values()->all())
        ->toBe(collect($allPermissionNames)->sort()->values()->all());

    expect($manager->permissions->pluck('name')->contains(fn (string $name): bool => str_ends_with($name, '.delete')))
        ->toBeFalse();
});

it('keeps cashier access limited to sales-facing modules and excludes settings/users management', function (): void {
    $cashier = Role::query()->where('name', RoleName::CASHIER->value)->firstOrFail();
    $cashierPermissionNames = $cashier->permissions->pluck('name');

    expect($cashierPermissionNames->contains('sales.view'))->toBeTrue();
    expect($cashierPermissionNames->contains('purchases.view'))->toBeFalse();
    expect($cashierPermissionNames->contains('settings.view'))->toBeFalse();
    expect($cashierPermissionNames->contains('users.view'))->toBeFalse();
});

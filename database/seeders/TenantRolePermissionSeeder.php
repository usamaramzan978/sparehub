<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

final class TenantRolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        resolve(PermissionRegistrar::class)->forgetCachedPermissions();

        $guardName = 'web';

        $permissions = collect($this->permissions())
            ->map(fn (string $name) => Permission::query()->firstOrCreate([
                'name' => $name,
                'guard_name' => $guardName,
            ]));

        $owner = Role::query()->firstOrCreate([
            'name' => RoleName::TENANT_OWNER->value,
            'guard_name' => $guardName,
        ]);
        $admin = Role::query()->firstOrCreate([
            'name' => RoleName::ADMIN->value,
            'guard_name' => $guardName,
        ]);
        $manager = Role::query()->firstOrCreate([
            'name' => RoleName::MANAGER->value,
            'guard_name' => $guardName,
        ]);
        $cashier = Role::query()->firstOrCreate([
            'name' => RoleName::CASHIER->value,
            'guard_name' => $guardName,
        ]);

        $owner->syncPermissions($permissions);
        $admin->syncPermissions($permissions);

        $managerPermissions = $permissions->reject(fn (Permission $permission): bool => str_ends_with($permission->name, '.delete'));
        $manager->syncPermissions($managerPermissions);

        $cashierResourcePrefixes = [
            'pos.',
            'sales.',
            'sale-items.',
            'sale-payments.',
            'sale-holds.',
            'customers.',
            'customer-vehicles.',
            'end-of-day.',
            'reports.',
            'profile.',
        ];

        $cashierPermissions = $permissions->filter(function (Permission $permission) use ($cashierResourcePrefixes): bool {
            foreach ($cashierResourcePrefixes as $prefix) {
                if (str_starts_with($permission->name, $prefix)) {
                    return true;
                }
            }

            return false;
        });
        $cashier->syncPermissions($cashierPermissions);
    }

    /**
     * @return array<int, string>
     */
    private function permissions(): array
    {
        $resources = [
            'branches',
            'categories',
            'brands',
            'units',
            'taxes',
            'users',
            'roles',
            'permissions',
            'customers',
            'customer-vehicles',
            'vendors',
            'products',
            'product-prices',
            'service-catalog',
            'job-cards',
            'pos',
            'sales',
            'sale-items',
            'sale-payments',
            'sale-holds',
            'purchases',
            'purchase-items',
            'purchase-returns',
            'purchase-return-items',
            'vendor-payments',
            'employee-attendances',
            'employee-salaries',
            'end-of-day',
            'settings',
            'reports',
            'profile',
        ];

        $actions = ['view', 'create', 'update', 'delete'];

        $permissions = [];
        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                $permissions[] = $resource.'.'.$action;
            }
        }

        return $permissions;
    }
}

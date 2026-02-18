<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\BranchStatus;
use App\Enums\LoginUserType;
use App\Enums\TenantStatus;
use App\Enums\UserStatus;
use App\Models\Branch;
use App\Models\LoginMap;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

final class ManualMultiTenantAuthSeeder extends Seeder
{
    private const string SHARED_EMAIL = 'shared.auth@sparehub.local';

    private const string SHARED_PASSWORD = 'Password@123';

    public function run(): void
    {
        $tenantDefinitions = [
            [
                'slug' => 'auth-tenant-a',
                'name' => 'Auth Tenant A',
                'user_name' => 'Shared User A',
            ],
            [
                'slug' => 'auth-tenant-b',
                'name' => 'Auth Tenant B',
                'user_name' => 'Shared User B',
            ],
        ];

        $tenants = [];

        foreach ($tenantDefinitions as $definition) {
            $tenants[] = Tenant::query()->firstOrCreate(
                ['slug' => $definition['slug']],
                [
                    'name' => $definition['name'],
                    'slug' => $definition['slug'],
                    'status' => TenantStatus::ACTIVE->value,
                    'data' => [
                        'owner_name' => $definition['user_name'],
                        'owner_email' => self::SHARED_EMAIL,
                    ],
                ]
            );
        }

        Artisan::call('tenants:migrate', [
            '--force' => true,
            '--tenants' => collect($tenants)->pluck('id')->all(),
        ]);

        foreach ($tenantDefinitions as $index => $definition) {
            $tenant = $tenants[$index];
            $user = $tenant->run(function () use ($definition): User {
                $branch = Branch::query()->firstOrCreate(
                    ['code' => 'MAIN'],
                    [
                        'name' => 'Main Branch',
                        'status' => BranchStatus::ACTIVE->value,
                    ]
                );

                return User::query()->updateOrCreate(
                    ['email' => self::SHARED_EMAIL],
                    [
                        'branch_id' => $branch->id,
                        'name' => $definition['user_name'],
                        'password' => Hash::make(self::SHARED_PASSWORD),
                        'status' => UserStatus::ACTIVE->value,
                        'email_verified_at' => now(),
                    ]
                );
            });

            LoginMap::query()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'type' => LoginUserType::USER->value,
                    'type_id' => $user->id,
                ],
                [
                    'email' => self::SHARED_EMAIL,
                    'password' => $user->password,
                    'status' => true,
                ]
            );
        }
    }
}

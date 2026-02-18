<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RecordStatus;
use App\Enums\TenantStatus;
use App\Enums\UserStatus;
use App\Models\Domain;
use App\Models\Plan;
use App\Models\SystemUser;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class CentralDemoSeeder extends Seeder
{
    public function run(): void
    {
        $starterPlan = Plan::query()->firstOrCreate(
            ['code' => 'STARTER'],
            [
                'name' => 'Starter',
                'description' => 'Small workshops and single branch stores.',
                'monthly_price' => 29,
                'annual_price' => 290,
                'max_users' => 5,
                'max_branches' => 1,
                'status' => RecordStatus::ACTIVE->value,
            ]
        );

        Plan::query()->firstOrCreate(
            ['code' => 'BUSINESS'],
            [
                'name' => 'Business',
                'description' => 'Growing operations with multi-branch support.',
                'monthly_price' => 99,
                'annual_price' => 990,
                'max_users' => 30,
                'max_branches' => 10,
                'status' => RecordStatus::ACTIVE->value,
            ]
        );

        SystemUser::query()->updateOrCreate(
            ['email' => 'systemowner@local.com'],
            [
                'name' => 'System Owner',
                'password' => Hash::make('password'),
                'status' => UserStatus::ACTIVE->value,
            ]
        );

        $tenant = Tenant::query()->firstOrCreate(
            ['slug' => 'tenant'],
            [
                'name' => 'Demo Tenant',
                'slug' => 'tenant',
                'status' => TenantStatus::ACTIVE->value,
                'plan_id' => $starterPlan->id,
                'data' => [
                    'owner_name' => 'Demo Owner',
                    'owner_email' => 'owner@demo.local',
                ],
            ]
        );

        if (! $tenant->plan_id) {
            $tenant->update(['plan_id' => $starterPlan->id]);
        }

        Domain::query()->firstOrCreate(['domain' => '127.0.0.1'], ['tenant_id' => $tenant->id]);
    }
}

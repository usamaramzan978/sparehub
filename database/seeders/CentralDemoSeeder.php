<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\TenantStatus;
use App\Models\Domain;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

final class CentralDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::query()->firstOrCreate(['slug' => 'tenant'], [
            'name' => 'Tenant',
            'slug' => 'tenant',
            'status' => TenantStatus::ACTIVE->value,
            'data' => [],
        ]);

        Domain::query()->firstOrCreate(['domain' => '127.0.0.1'], ['tenant_id' => $tenant->id]);
    }
}

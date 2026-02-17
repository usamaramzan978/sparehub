<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

final class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (tenancy()->initialized) {
            $this->call(TenantDemoSeeder::class);

            return;
        }

        $this->call(CentralDemoSeeder::class);

        // Keep tenant DB schema in sync before tenant demo seed runs.
        Artisan::call('tenants:migrate', ['--force' => true]);
        Artisan::call('tenants:seed', ['--class' => TenantDemoSeeder::class, '--force' => true]);
    }
}

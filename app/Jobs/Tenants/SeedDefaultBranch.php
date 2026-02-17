<?php

declare(strict_types=1);

namespace App\Jobs\Tenants;

use Database\Seeders\TenantBootstrapSeeder;
use Illuminate\Support\Facades\Artisan;
use Stancl\Tenancy\Contracts\TenantWithDatabase;

final readonly class SeedDefaultBranch
{
    public function __construct(private TenantWithDatabase $tenant) {}

    public function handle(): void
    {
        Artisan::call('tenants:seed', [
            '--tenants' => [$this->tenant->getTenantKey()],
            '--class' => TenantBootstrapSeeder::class,
        ]);
    }
}

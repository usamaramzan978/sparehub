<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Tax;
use App\Models\TenantSetting;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

final class TenantBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::query()->firstOrCreate(
            ['code' => 'MAIN'],
            ['name' => 'Main Branch', 'status' => 'active']
        );

        $warehouse = Warehouse::query()->firstOrCreate(
            ['branch_id' => $branch->id, 'code' => 'WH-01'],
            [
                'name' => 'Main Warehouse',
                'status' => 'active',
            ]
        );

        if (! $branch->warehouse_id) {
            $branch->warehouse_id = $warehouse->id;
            $branch->save();
        }

        Tax::query()->firstOrCreate(
            ['code' => 'VAT'],
            ['name' => 'VAT', 'rate' => 5.00, 'status' => 'active']
        );

        TenantSetting::query()->firstOrCreate(['branch_id' => $branch->id], [
            'company_name' => $branch->name,
            'support_email' => 'support@tenant.local',
            'support_phone' => '+1-555-0200',
            'enable_two_factor' => false,
            'notify_email' => true,
            'enable_otp' => false,
            'otp_length' => 6,
            'otp_expiry_minutes' => 10,
        ]);
    }
}

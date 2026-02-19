<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\BranchStatus;
use App\Enums\LoginUserType;
use App\Enums\RecordStatus;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\LoginMap;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ServiceCatalog;
use App\Models\Tax;
use App\Models\TenantSetting;
use App\Models\Unit;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class TenantDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(TenantRolePermissionSeeder::class);

        $branch = Branch::query()->firstOrCreate(
            ['code' => 'MAIN'],
            [
                'name' => 'Main Branch',
                'status' => BranchStatus::ACTIVE->value,
                'is_default' => true,
                'city' => 'Lahore',
            ]
        );

        $warehouse = Warehouse::query()->firstOrCreate(
            [
                'branch_id' => $branch->id,
                'code' => 'MAIN-WH',
            ],
            [
                'name' => 'Main Warehouse',
                'status' => RecordStatus::ACTIVE->value,
            ]
        );

        if ($branch->warehouse_id !== $warehouse->id) {
            $branch->update(['warehouse_id' => $warehouse->id]);
        }

        TenantSetting::query()->firstOrCreate(
            ['branch_id' => $branch->id],
            [
                'company_name' => 'Sparehub Demo Auto Shop',
                'support_email' => 'support@sparehub.local',
                'support_phone' => '+92-300-0000000',
                'enable_two_factor' => false,
                'notify_email' => true,
                'enable_otp' => false,
                'otp_length' => 6,
                'otp_expiry_minutes' => 10,
            ]
        );

        $owner = User::query()->firstOrCreate(
            ['email' => 'owner@demo.local'],
            [
                'branch_id' => $branch->id,
                'name' => 'Demo Owner',
                'phone' => '+92-300-1111111',
                'password' => Hash::make('password'),
                'status' => UserStatus::ACTIVE->value,
                'email_verified_at' => now(),
            ]
        );
        $owner->assignRole(RoleName::TENANT_OWNER->value);

        $tenantId = (string) tenant('id');

        LoginMap::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'type' => LoginUserType::USER->value,
                'type_id' => $owner->id,
            ],
            [
                'email' => $owner->email,
                'password' => $owner->password,
                'status' => true,
            ]
        );

        $unit = Unit::query()->firstOrCreate(
            ['code' => 'PCS'],
            ['name' => 'Pieces', 'is_fractional' => false, 'status' => RecordStatus::ACTIVE->value]
        );

        $tax = Tax::query()->firstOrCreate(
            ['code' => 'GST-18'],
            ['name' => 'GST 18%', 'rate' => 18, 'is_inclusive' => false, 'status' => RecordStatus::ACTIVE->value]
        );

        $category = Category::query()->firstOrCreate(
            ['slug' => 'engine-parts'],
            ['name' => 'Engine Parts', 'status' => RecordStatus::ACTIVE->value]
        );

        $brand = Brand::query()->firstOrCreate(
            ['slug' => 'honda'],
            ['name' => 'Honda', 'status' => RecordStatus::ACTIVE->value]
        );

        $product = Product::query()->firstOrCreate(
            ['sku' => 'SPK-CHAIN-001'],
            [
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'default_tax_id' => $tax->id,
                'default_unit_id' => $unit->id,
                'part_number' => 'CHAIN-001',
                'barcode' => '100000000001',
                'name' => 'Bike Chain Set',
                'description' => 'Demo spare part for POS testing',
                'track_stock' => true,
                'status' => RecordStatus::ACTIVE->value,
            ]
        );

        ProductPrice::query()->firstOrCreate(
            [
                'product_id' => $product->id,
                'branch_id' => $branch->id,
            ],
            [
                'cost' => 950,
                'mrp' => 1400,
                'retail_price' => 1250,
                'wholesale_price' => 1150,
                'effective_from' => now(),
            ]
        );

        ServiceCatalog::query()->firstOrCreate(
            [
                'branch_id' => $branch->id,
                'code' => 'TUNE-UP',
            ],
            [
                'default_tax_id' => $tax->id,
                'name' => 'Bike Tuning',
                'category' => 'Workshop',
                'base_price' => 800,
                'duration_minutes' => 45,
                'status' => RecordStatus::ACTIVE->value,
            ]
        );

        Customer::query()->firstOrCreate(
            [
                'branch_id' => $branch->id,
                'code' => 'CUST-0001',
            ],
            [
                'name' => 'Walk-in Customer',
                'phone' => '+92-300-2222222',
                'city' => 'Lahore',
                'status' => RecordStatus::ACTIVE->value,
            ]
        );

        Vendor::query()->firstOrCreate(
            [
                'branch_id' => $branch->id,
                'code' => 'VEND-0001',
            ],
            [
                'name' => 'Demo Parts Supplier',
                'phone' => '+92-300-3333333',
                'city' => 'Lahore',
                'status' => RecordStatus::ACTIVE->value,
            ]
        );
    }
}

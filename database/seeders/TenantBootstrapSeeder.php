<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\BranchStatus;
use App\Enums\RecordStatus;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ServiceCatalog;
use App\Models\Tax;
use App\Models\TenantSetting;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use RuntimeException;

final class TenantBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = tenant();

        throw_unless($tenant, RuntimeException::class, 'Tenant context is required for TenantBootstrapSeeder.');

        $tenant->run(function (): void {

            /**
             * ---------------------------------------------------------
             * Branch + Warehouse
             * ---------------------------------------------------------
             */
            $branch = Branch::query()->firstOrCreate(
                ['code' => 'MAIN'],
                [
                    'name' => 'Main Branch',
                    'status' => BranchStatus::ACTIVE->value,
                    'is_default' => true,
                ]
            );

            $branch = Branch::query()->whereKey($branch->id)->firstOrFail();

            $warehouse = Warehouse::query()->firstOrCreate(
                [
                    'branch_id' => $branch->id,
                    'code' => 'WH-01',
                ],
                [
                    'name' => 'Main Warehouse',
                    'status' => RecordStatus::ACTIVE->value,
                ]
            );

            if ($branch->warehouse_id !== $warehouse->id) {
                $branch->update(['warehouse_id' => $warehouse->id]);
            }

            /**
             * ---------------------------------------------------------
             * Taxes (Pakistan Bike Spare Business)
             * ---------------------------------------------------------
             */
            $taxes = [
                ['code' => 'GST',    'name' => 'GST (General Sales Tax)', 'rate' => 18.00],
                ['code' => 'EXEMPT', 'name' => 'Tax Exempt',             'rate' => 0.00],
            ];

            foreach ($taxes as $t) {
                Tax::query()->firstOrCreate(
                    ['code' => $t['code']],
                    [
                        'name' => $t['name'],
                        'rate' => $t['rate'],
                        'status' => RecordStatus::ACTIVE->value,
                    ]
                );
            }

            /**
             * ---------------------------------------------------------
             * Units (Bike Spare Units)
             * ---------------------------------------------------------
             */
            $units = [
                ['code' => 'PCS', 'name' => 'Pieces', 'is_fractional' => false],
                ['code' => 'SET', 'name' => 'Set',    'is_fractional' => false],
                ['code' => 'LTR', 'name' => 'Liter',  'is_fractional' => true],
            ];

            foreach ($units as $u) {
                Unit::query()->firstOrCreate(
                    ['code' => $u['code']],
                    [
                        'name' => $u['name'],
                        'is_fractional' => $u['is_fractional'],
                        'status' => RecordStatus::ACTIVE->value,
                    ]
                );
            }

            /**
             * ---------------------------------------------------------
             * Categories (Bike Spare Parts)
             * ---------------------------------------------------------
             */
            $categories = [
                ['slug' => 'engine-parts',      'name' => 'Engine Parts'],
                ['slug' => 'brake-parts',       'name' => 'Brake Parts'],
                ['slug' => 'electrical-parts',  'name' => 'Electrical Parts'],
                ['slug' => 'body-parts',        'name' => 'Body Parts'],
                ['slug' => 'suspension-parts',  'name' => 'Suspension Parts'],
                ['slug' => 'lubricants',        'name' => 'Lubricants & Oils'],
            ];

            foreach ($categories as $c) {
                Category::query()->firstOrCreate(
                    ['slug' => $c['slug']],
                    [
                        'name' => $c['name'],
                        'status' => RecordStatus::ACTIVE->value,
                    ]
                );
            }

            /**
             * ---------------------------------------------------------
             * Brands (Pakistan Bike Market)
             * ---------------------------------------------------------
             */
            $brands = [
                ['slug' => 'honda',        'name' => 'Honda'],
                ['slug' => 'suzuki',       'name' => 'Suzuki'],
                ['slug' => 'yamaha',       'name' => 'Yamaha'],
                ['slug' => 'united',       'name' => 'United'],
                ['slug' => 'road-prince',  'name' => 'Road Prince'],
                ['slug' => 'unique',       'name' => 'Unique'],
                ['slug' => 'crown',        'name' => 'Crown'],
                ['slug' => 'osaka',        'name' => 'Osaka Battery'],
                ['slug' => 'atlas',        'name' => 'Atlas Honda Genuine'],
            ];

            foreach ($brands as $b) {
                Brand::query()->firstOrCreate(
                    ['slug' => $b['slug']],
                    [
                        'name' => $b['name'],
                        'status' => RecordStatus::ACTIVE->value,
                    ]
                );
            }

            /**
             * ---------------------------------------------------------
             * Fetch References
             * ---------------------------------------------------------
             */
            $taxGST = Tax::query()->where('code', 'GST')->firstOrFail();

            $unitPCS = Unit::query()->where('code', 'PCS')->firstOrFail();
            $unitSET = Unit::query()->where('code', 'SET')->firstOrFail();
            $unitLTR = Unit::query()->where('code', 'LTR')->firstOrFail();

            $catEngine = Category::query()->where('slug', 'engine-parts')->firstOrFail();
            $catBrake = Category::query()->where('slug', 'brake-parts')->firstOrFail();
            $catElectrical = Category::query()->where('slug', 'electrical-parts')->firstOrFail();
            $catBody = Category::query()->where('slug', 'body-parts')->firstOrFail();
            $catSuspension = Category::query()->where('slug', 'suspension-parts')->firstOrFail();
            $catOil = Category::query()->where('slug', 'lubricants')->firstOrFail();

            $brandHonda = Brand::query()->where('slug', 'honda')->firstOrFail();
            $brandSuzuki = Brand::query()->where('slug', 'suzuki')->firstOrFail();
            $brandYamaha = Brand::query()->where('slug', 'yamaha')->firstOrFail();
            $brandUnique = Brand::query()->where('slug', 'unique')->firstOrFail();
            $brandUnited = Brand::query()->where('slug', 'united')->firstOrFail();
            $brandOsaka = Brand::query()->where('slug', 'osaka')->firstOrFail();
            $brandAtlas = Brand::query()->where('slug', 'atlas')->firstOrFail();

            /**
             * ---------------------------------------------------------
             * Products (Pakistan Realistic Bike Spare Data)
             * ---------------------------------------------------------
             */
            $products = [

                // Honda CD70
                [
                    'sku' => 'HON-CD70-CHAINSET',
                    'category_id' => $catEngine->id,
                    'brand_id' => $brandHonda->id,
                    'unit_id' => $unitSET->id,
                    'tax_id' => $taxGST->id,
                    'part_number' => 'CD70-CS-01',
                    'barcode' => '300000000001',
                    'name' => 'Chain Sprocket Kit CD70',
                    'description' => 'Chain sprocket kit for Honda CD70 (local)',
                    'cost' => 950,
                    'mrp' => 1400,
                    'retail_price' => 1250,
                    'wholesale_price' => 1150,
                ],
                [
                    'sku' => 'HON-CD70-CLUTCHPLATE',
                    'category_id' => $catEngine->id,
                    'brand_id' => $brandHonda->id,
                    'unit_id' => $unitSET->id,
                    'tax_id' => $taxGST->id,
                    'part_number' => 'CD70-CP-01',
                    'barcode' => '300000000002',
                    'name' => 'Clutch Plate Set CD70',
                    'description' => 'Clutch plate set for Honda CD70',
                    'cost' => 520,
                    'mrp' => 750,
                    'retail_price' => 680,
                    'wholesale_price' => 640,
                ],
                [
                    'sku' => 'HON-CD70-SPARKPLUG',
                    'category_id' => $catElectrical->id,
                    'brand_id' => $brandAtlas->id,
                    'unit_id' => $unitPCS->id,
                    'tax_id' => $taxGST->id,
                    'part_number' => 'NGK-BPR6ES',
                    'barcode' => '300000000003',
                    'name' => 'Spark Plug (NGK)',
                    'description' => 'NGK spark plug for CD70/CG125',
                    'cost' => 180,
                    'mrp' => 300,
                    'retail_price' => 250,
                    'wholesale_price' => 220,
                ],
                [
                    'sku' => 'HON-CD70-AIRFILTER',
                    'category_id' => $catEngine->id,
                    'brand_id' => $brandHonda->id,
                    'unit_id' => $unitPCS->id,
                    'tax_id' => $taxGST->id,
                    'part_number' => 'CD70-AF-01',
                    'barcode' => '300000000004',
                    'name' => 'Air Filter CD70',
                    'description' => 'Air filter element for Honda CD70',
                    'cost' => 200,
                    'mrp' => 350,
                    'retail_price' => 300,
                    'wholesale_price' => 270,
                ],
                [
                    'sku' => 'HON-CD70-BRAKESHOE',
                    'category_id' => $catBrake->id,
                    'brand_id' => $brandHonda->id,
                    'unit_id' => $unitSET->id,
                    'tax_id' => $taxGST->id,
                    'part_number' => 'CD70-BS-01',
                    'barcode' => '300000000005',
                    'name' => 'Brake Shoe Set CD70',
                    'description' => 'Rear brake shoe set for Honda CD70',
                    'cost' => 420,
                    'mrp' => 650,
                    'retail_price' => 600,
                    'wholesale_price' => 550,
                ],

                // Honda CG125
                [
                    'sku' => 'HON-CG125-PISTONKIT',
                    'category_id' => $catEngine->id,
                    'brand_id' => $brandHonda->id,
                    'unit_id' => $unitSET->id,
                    'tax_id' => $taxGST->id,
                    'part_number' => 'CG125-PT-01',
                    'barcode' => '300000000006',
                    'name' => 'Piston Kit CG125',
                    'description' => 'Piston kit for Honda CG125 (standard)',
                    'cost' => 1200,
                    'mrp' => 1700,
                    'retail_price' => 1550,
                    'wholesale_price' => 1450,
                ],
                [
                    'sku' => 'HON-CG125-CARBURETOR',
                    'category_id' => $catEngine->id,
                    'brand_id' => $brandHonda->id,
                    'unit_id' => $unitPCS->id,
                    'tax_id' => $taxGST->id,
                    'part_number' => 'CG125-CARB-01',
                    'barcode' => '300000000007',
                    'name' => 'Carburetor CG125',
                    'description' => 'Carburetor for Honda CG125 (standard)',
                    'cost' => 2200,
                    'mrp' => 2900,
                    'retail_price' => 2700,
                    'wholesale_price' => 2550,
                ],
                [
                    'sku' => 'HON-CG125-CHAINSET',
                    'category_id' => $catEngine->id,
                    'brand_id' => $brandHonda->id,
                    'unit_id' => $unitSET->id,
                    'tax_id' => $taxGST->id,
                    'part_number' => 'CG125-CS-01',
                    'barcode' => '300000000008',
                    'name' => 'Chain Sprocket Kit CG125',
                    'description' => 'Chain sprocket kit for Honda CG125',
                    'cost' => 1350,
                    'mrp' => 1850,
                    'retail_price' => 1700,
                    'wholesale_price' => 1600,
                ],

                // Yamaha YBR
                [
                    'sku' => 'YAM-YBR-BRAKEPAD',
                    'category_id' => $catBrake->id,
                    'brand_id' => $brandYamaha->id,
                    'unit_id' => $unitSET->id,
                    'tax_id' => $taxGST->id,
                    'part_number' => 'YBR-BP-01',
                    'barcode' => '300000000009',
                    'name' => 'Brake Pad Set YBR',
                    'description' => 'Front brake pad set for Yamaha YBR',
                    'cost' => 680,
                    'mrp' => 950,
                    'retail_price' => 880,
                    'wholesale_price' => 820,
                ],
                [
                    'sku' => 'YAM-YBR-AIRFILTER',
                    'category_id' => $catEngine->id,
                    'brand_id' => $brandYamaha->id,
                    'unit_id' => $unitPCS->id,
                    'tax_id' => $taxGST->id,
                    'part_number' => 'YBR-AF-01',
                    'barcode' => '300000000010',
                    'name' => 'Air Filter YBR',
                    'description' => 'Air filter for Yamaha YBR 125',
                    'cost' => 450,
                    'mrp' => 700,
                    'retail_price' => 650,
                    'wholesale_price' => 610,
                ],

                // United / Unique
                [
                    'sku' => 'UNI-CD70-HEADLIGHT',
                    'category_id' => $catElectrical->id,
                    'brand_id' => $brandUnited->id,
                    'unit_id' => $unitPCS->id,
                    'tax_id' => $taxGST->id,
                    'part_number' => 'UNI-HL-01',
                    'barcode' => '300000000011',
                    'name' => 'Headlight Bulb 12V',
                    'description' => 'Headlight bulb for CD70/Chinese bikes',
                    'cost' => 120,
                    'mrp' => 200,
                    'retail_price' => 180,
                    'wholesale_price' => 160,
                ],
                [
                    'sku' => 'UNQ-CD70-SEATCOVER',
                    'category_id' => $catBody->id,
                    'brand_id' => $brandUnique->id,
                    'unit_id' => $unitPCS->id,
                    'tax_id' => $taxGST->id,
                    'part_number' => 'UNQ-SC-01',
                    'barcode' => '300000000012',
                    'name' => 'Seat Cover CD70',
                    'description' => 'Seat cover for CD70 (black stitched)',
                    'cost' => 300,
                    'mrp' => 500,
                    'retail_price' => 450,
                    'wholesale_price' => 420,
                ],

                // Suspension
                [
                    'sku' => 'HON-CD70-FORKOILSEAL',
                    'category_id' => $catSuspension->id,
                    'brand_id' => $brandHonda->id,
                    'unit_id' => $unitSET->id,
                    'tax_id' => $taxGST->id,
                    'part_number' => 'CD70-FOS-01',
                    'barcode' => '300000000013',
                    'name' => 'Fork Oil Seal Set CD70',
                    'description' => 'Front fork oil seal set for CD70',
                    'cost' => 250,
                    'mrp' => 400,
                    'retail_price' => 350,
                    'wholesale_price' => 320,
                ],

                // Battery
                [
                    'sku' => 'OSA-BATTERY-5L',
                    'category_id' => $catElectrical->id,
                    'brand_id' => $brandOsaka->id,
                    'unit_id' => $unitPCS->id,
                    'tax_id' => $taxGST->id,
                    'part_number' => 'OSK-5L',
                    'barcode' => '300000000014',
                    'name' => 'Osaka Battery 5L',
                    'description' => 'Osaka battery 5L for CD70/CG125',
                    'cost' => 2400,
                    'mrp' => 3100,
                    'retail_price' => 2950,
                    'wholesale_price' => 2800,
                ],

                // Oils
                [
                    'sku' => 'OIL-ZIC-10W40-1L',
                    'category_id' => $catOil->id,
                    'brand_id' => $brandHonda->id,
                    'unit_id' => $unitLTR->id,
                    'tax_id' => $taxGST->id,
                    'part_number' => 'ZIC-10W40-1L',
                    'barcode' => '300000000015',
                    'name' => 'ZIC Engine Oil 1L (10W-40)',
                    'description' => 'ZIC bike engine oil 1 liter',
                    'cost' => 750,
                    'mrp' => 1100,
                    'retail_price' => 980,
                    'wholesale_price' => 930,
                ],
                [
                    'sku' => 'OIL-CASTROL-20W50-1L',
                    'category_id' => $catOil->id,
                    'brand_id' => $brandHonda->id,
                    'unit_id' => $unitLTR->id,
                    'tax_id' => $taxGST->id,
                    'part_number' => 'CASTROL-20W50-1L',
                    'barcode' => '300000000016',
                    'name' => 'Castrol Engine Oil 1L (20W-50)',
                    'description' => 'Castrol engine oil for CD70/CG125',
                    'cost' => 720,
                    'mrp' => 1050,
                    'retail_price' => 950,
                    'wholesale_price' => 900,
                ],
            ];

            foreach ($products as $p) {

                $product = Product::query()->firstOrCreate(
                    ['sku' => $p['sku']],
                    [
                        'category_id' => $p['category_id'],
                        'brand_id' => $p['brand_id'],
                        'default_tax_id' => $p['tax_id'],
                        'default_unit_id' => $p['unit_id'],
                        'part_number' => $p['part_number'],
                        'barcode' => $p['barcode'],
                        'name' => $p['name'],
                        'description' => $p['description'],
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
                        'cost' => $p['cost'],
                        'mrp' => $p['mrp'],
                        'retail_price' => $p['retail_price'],
                        'wholesale_price' => $p['wholesale_price'],
                        'effective_from' => now(),
                    ]
                );
            }

            /**
             * ---------------------------------------------------------
             * Services (Bike Workshop)
             * ---------------------------------------------------------
             */
            $services = [
                ['code' => 'TUNE-UP',       'name' => 'Bike Tuning',               'category' => 'Workshop', 'base_price' => 800, 'duration_minutes' => 45],
                ['code' => 'OIL-CHANGE',    'name' => 'Engine Oil Change',         'category' => 'Workshop', 'base_price' => 300, 'duration_minutes' => 15],
                ['code' => 'CHAIN-SERVICE', 'name' => 'Chain Adjustment + Grease', 'category' => 'Workshop', 'base_price' => 250, 'duration_minutes' => 15],
                ['code' => 'BRAKE-SERVICE', 'name' => 'Brake Service',             'category' => 'Workshop', 'base_price' => 400, 'duration_minutes' => 20],
                ['code' => 'CLUTCH-FIT',    'name' => 'Clutch Plate Fitting',      'category' => 'Workshop', 'base_price' => 700, 'duration_minutes' => 35],
                ['code' => 'PUNCTURE',      'name' => 'Puncture Repair',           'category' => 'Workshop', 'base_price' => 100, 'duration_minutes' => 10],
                ['code' => 'CARB-SERVICE',  'name' => 'Carburetor Service',        'category' => 'Workshop', 'base_price' => 600, 'duration_minutes' => 30],
                ['code' => 'PLUG-CLEAN',    'name' => 'Spark Plug Cleaning',       'category' => 'Workshop', 'base_price' => 80,  'duration_minutes' => 5],
            ];

            foreach ($services as $s) {
                ServiceCatalog::query()->firstOrCreate(
                    [
                        'branch_id' => $branch->id,
                        'code' => $s['code'],
                    ],
                    [
                        'default_tax_id' => $taxGST->id,
                        'name' => $s['name'],
                        'category' => $s['category'],
                        'base_price' => $s['base_price'],
                        'duration_minutes' => $s['duration_minutes'],
                        'is_taxable' => true,
                        'status' => RecordStatus::ACTIVE->value,
                    ]
                );
            }

            /**
             * ---------------------------------------------------------
             * Customers
             * ---------------------------------------------------------
             */
            $customers = [
                ['code' => 'CUST-0001', 'name' => 'Walk-in Customer',  'phone' => '',             'city' => ''],
                ['code' => 'CUST-0002', 'name' => 'Ahmad Bike Center', 'phone' => '0321-1234567', 'city' => 'Lahore'],
                ['code' => 'CUST-0003', 'name' => 'Ali Mechanic',      'phone' => '0301-5558899', 'city' => 'Kasur'],
                ['code' => 'CUST-0004', 'name' => 'Rana Bike House',   'phone' => '0333-9090909', 'city' => 'Faisalabad'],
            ];

            foreach ($customers as $c) {
                Customer::query()->firstOrCreate(
                    [
                        'branch_id' => $branch->id,
                        'code' => $c['code'],
                    ],
                    [
                        'name' => $c['name'],
                        'phone' => $c['phone'],
                        'city' => $c['city'],
                        'status' => RecordStatus::ACTIVE->value,
                    ]
                );
            }

            /**
             * ---------------------------------------------------------
             * Tenant Settings
             * ---------------------------------------------------------
             */
            TenantSetting::query()->firstOrCreate(
                ['branch_id' => $branch->id],
                [
                    'company_name' => 'SpareHub Bike POS - '.$branch->name,
                    'support_email' => 'support@sparehub.pk',
                    'support_phone' => '0300-0000000',
                    'enable_two_factor' => false,
                    'notify_email' => true,
                    'enable_otp' => false,
                    'otp_length' => 6,
                    'otp_expiry_minutes' => 10,
                ]
            );
        });
    }
}

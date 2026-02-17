<?php

declare(strict_types=1);

use App\Enums\BranchStatus;
use App\Enums\RecordStatus;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ServiceCatalog;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    Config::set('database.connections.tenant', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]);

    Artisan::call('migrate:fresh', [
        '--database' => 'tenant',
        '--path' => database_path('migrations/tenant'),
        '--realpath' => true,
        '--force' => true,
    ]);

    $this->withoutMiddleware();
});

/**
 * @return array{branch:Branch,user:User,product:Product,service:ServiceCatalog}
 */
function createPosFixture(): array
{
    $branch = Branch::query()->create([
        'code' => 'MAIN',
        'name' => 'Main Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $user = User::query()->create([
        'branch_id' => $branch->id,
        'name' => 'POS Cashier',
        'email' => 'cashier@example.test',
        'password' => Hash::make('password'),
        'status' => 'active',
    ]);

    $category = Category::query()->create([
        'name' => 'Engine',
        'slug' => 'engine',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $product = Product::query()->create([
        'category_id' => $category->id,
        'sku' => 'ENG-001',
        'name' => 'Engine Oil',
        'track_stock' => true,
        'is_service_item' => false,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    ProductPrice::query()->create([
        'product_id' => $product->id,
        'branch_id' => $branch->id,
        'cost' => 100,
        'mrp' => 200,
        'retail_price' => 150,
        'wholesale_price' => 140,
        'effective_from' => now(),
    ]);

    $service = ServiceCatalog::query()->create([
        'branch_id' => $branch->id,
        'code' => 'SRV-001',
        'name' => 'Oil Change',
        'category' => 'Workshop',
        'base_price' => 250,
        'is_taxable' => false,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    return [
        'branch' => $branch,
        'user' => $user,
        'product' => $product,
        'service' => $service,
    ];
}

dataset('pos_invoice_type_cases', [
    'product-only' => ['product'],
    'service-only' => ['service'],
    'mixed' => ['mixed'],
]);

it('stores pos sale for product, service, and mixed carts', function (string $case): void {
    $fixture = createPosFixture();
    $tenantId = 'test-tenant-id';

    $this->actingAs($fixture['user'], 'user');
    $this->withSession(['tenant.current_branch_id' => $fixture['branch']->id]);

    $items = match ($case) {
        'product' => [[
            'type' => 'product',
            'ref_id' => $fixture['product']->id,
            'qty' => 2,
            'price' => 150,
            'tax_rate' => 0,
            'tax_inclusive' => false,
            'name' => 'Engine Oil',
        ]],
        'service' => [[
            'type' => 'service',
            'ref_id' => $fixture['service']->id,
            'qty' => 1,
            'price' => 250,
            'tax_rate' => 0,
            'tax_inclusive' => false,
            'name' => 'Oil Change',
        ]],
        default => [
            [
                'type' => 'product',
                'ref_id' => $fixture['product']->id,
                'qty' => 1,
                'price' => 150,
                'tax_rate' => 0,
                'tax_inclusive' => false,
                'name' => 'Engine Oil',
            ],
            [
                'type' => 'service',
                'ref_id' => $fixture['service']->id,
                'qty' => 1,
                'price' => 250,
                'tax_rate' => 0,
                'tax_inclusive' => false,
                'name' => 'Oil Change',
            ],
        ],
    };

    $response = $this->post(route('tenant.pos.store', ['tenant' => $tenantId]), [
        'status' => 'posted',
        'discount_type' => 'amount',
        'discount_value' => 0,
        'payment_mode' => 'cash',
        'cash_received' => 1000,
        'items' => $items,
    ]);

    $response->assertRedirect(route('tenant.pos.index', ['tenant' => $tenantId]));

    $sale = DB::connection('tenant')->table('sales')->latest('created_at')->first();

    expect($sale)->not->toBeNull();
    expect($sale->invoice_type)->toBe(match ($case) {
        'product' => 'product',
        'service' => 'service',
        default => 'mixed',
    });

    $saleItemsCount = DB::connection('tenant')->table('sale_items')->where('sale_id', $sale->id)->count();
    expect($saleItemsCount)->toBe(count($items));

    $salePaymentsCount = DB::connection('tenant')->table('sale_payments')->where('sale_id', $sale->id)->count();
    expect($salePaymentsCount)->toBe(1);
})->with('pos_invoice_type_cases');

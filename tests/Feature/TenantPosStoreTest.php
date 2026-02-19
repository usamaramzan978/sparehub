<?php

declare(strict_types=1);

use App\Enums\BranchStatus;
use App\Enums\RecordStatus;
use App\Models\Branch;
use App\Models\Category;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Sale;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

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

    Artisan::call('migrate', [
        '--path' => database_path('migrations/2026_02_16_034933_create_media_table.php'),
        '--realpath' => true,
        '--force' => true,
    ]);

    $this->withoutMiddleware();
});

/**
 * @return array{branch:Branch,user:User,warehouse:Warehouse,product:Product,service:ServiceCatalog}
 */
function createPosFixture(): array
{
    $branch = Branch::query()->create([
        'code' => 'MAIN',
        'name' => 'Main Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $warehouse = Warehouse::query()->create([
        'branch_id' => $branch->id,
        'code' => 'MAIN-WH',
        'name' => 'Main Warehouse',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $branch->update(['warehouse_id' => $warehouse->id]);

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
        'status' => RecordStatus::ACTIVE->value,
    ]);

    return [
        'branch' => $branch,
        'user' => $user,
        'warehouse' => $warehouse,
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

it('caps percent discount at subtotal in pos store', function (): void {
    $fixture = createPosFixture();
    $tenantId = 'test-tenant-id';

    $this->actingAs($fixture['user'], 'user');
    $this->withSession(['tenant.current_branch_id' => $fixture['branch']->id]);

    $response = $this->post(route('tenant.pos.store', ['tenant' => $tenantId]), [
        'status' => 'posted',
        'discount_type' => 'percent',
        'discount_value' => 200,
        'payment_mode' => 'cash',
        'items' => [[
            'type' => 'product',
            'ref_id' => $fixture['product']->id,
            'qty' => 1,
            'price' => 150,
            'tax_rate' => 0,
            'tax_inclusive' => false,
        ]],
    ]);

    $response->assertRedirect(route('tenant.pos.index', ['tenant' => $tenantId]));

    $sale = DB::connection('tenant')->table('sales')->latest('created_at')->first();
    expect((float) $sale->discount_total)->toBe(150.0);
    expect((float) $sale->grand_total)->toBe(0.0);
    expect((float) $sale->paid_total)->toBe(0.0);
});

it('uses min of cash received and grand total for debit payments', function (): void {
    $fixture = createPosFixture();
    $tenantId = 'test-tenant-id';

    $this->actingAs($fixture['user'], 'user');
    $this->withSession(['tenant.current_branch_id' => $fixture['branch']->id]);

    $response = $this->post(route('tenant.pos.store', ['tenant' => $tenantId]), [
        'status' => 'posted',
        'payment_mode' => 'debit',
        'cash_received' => 20,
        'items' => [[
            'type' => 'service',
            'ref_id' => $fixture['service']->id,
            'qty' => 1,
            'price' => 250,
            'tax_rate' => 0,
            'tax_inclusive' => false,
        ]],
    ]);

    $response->assertRedirect(route('tenant.pos.index', ['tenant' => $tenantId]));

    $sale = DB::connection('tenant')->table('sales')->latest('created_at')->first();
    expect((float) $sale->grand_total)->toBe(250.0);
    expect((float) $sale->paid_total)->toBe(20.0);
    expect((float) $sale->balance_due)->toBe(230.0);
});

it('stores online payment proof path for online mode', function (): void {
    Storage::fake('public');

    $fixture = createPosFixture();
    $tenantId = 'test-tenant-id';

    $this->actingAs($fixture['user'], 'user');
    $this->withSession(['tenant.current_branch_id' => $fixture['branch']->id]);

    $response = $this->post(route('tenant.pos.store', ['tenant' => $tenantId]), [
        'status' => 'posted',
        'payment_mode' => 'online',
        'payment_proof' => UploadedFile::fake()->image('proof.png'),
        'items' => [[
            'type' => 'service',
            'ref_id' => $fixture['service']->id,
            'qty' => 1,
            'price' => 250,
            'tax_rate' => 0,
            'tax_inclusive' => false,
        ]],
    ]);

    $response->assertRedirect(route('tenant.pos.index', ['tenant' => $tenantId]));

    $payment = DB::connection('tenant')->table('sale_payments')->latest('created_at')->first();
    expect($payment->payment_proof_path)->not->toBeNull();
    expect((string) $payment->payment_proof_path)->toContain('sale-payment-proofs');
    expect($payment->payment_method)->toBe('bank');

    $sale = DB::connection('tenant')->table('sales')->latest('created_at')->first();
    $media = DB::table('media')
        ->where('model_type', Sale::class)
        ->where('model_id', $sale->id)
        ->where('collection_name', 'online_payment_proof')
        ->first();

    expect($media)->not->toBeNull();
});

it('decrements inventory stock for tracked products when pos sale is stored', function (): void {
    $fixture = createPosFixture();
    $tenantId = 'test-tenant-id';

    InventoryStock::query()->create([
        'product_id' => $fixture['product']->id,
        'branch_id' => $fixture['branch']->id,
        'qty_on_hand' => 10,
        'qty_reserved' => 0,
        'avg_cost' => 0,
    ]);

    $this->actingAs($fixture['user'], 'user');
    $this->withSession(['tenant.current_branch_id' => $fixture['branch']->id]);

    $response = $this->post(route('tenant.pos.store', ['tenant' => $tenantId]), [
        'status' => 'posted',
        'discount_type' => 'amount',
        'discount_value' => 0,
        'payment_mode' => 'cash',
        'items' => [[
            'type' => 'product',
            'ref_id' => $fixture['product']->id,
            'qty' => 3,
            'price' => 150,
            'tax_rate' => 0,
            'tax_inclusive' => false,
        ]],
    ]);

    $response->assertRedirect(route('tenant.pos.index', ['tenant' => $tenantId]));

    $stock = InventoryStock::query()
        ->where('branch_id', $fixture['branch']->id)
        ->where('product_id', $fixture['product']->id)
        ->firstOrFail();

    expect((float) $stock->qty_on_hand)->toBe(7.0);
});

it('does not decrement inventory stock for non tracked products', function (): void {
    $fixture = createPosFixture();
    $tenantId = 'test-tenant-id';

    $fixture['product']->update(['track_stock' => false]);

    InventoryStock::query()->create([
        'product_id' => $fixture['product']->id,
        'branch_id' => $fixture['branch']->id,
        'qty_on_hand' => 10,
        'qty_reserved' => 0,
        'avg_cost' => 0,
    ]);

    $this->actingAs($fixture['user'], 'user');
    $this->withSession(['tenant.current_branch_id' => $fixture['branch']->id]);

    $response = $this->post(route('tenant.pos.store', ['tenant' => $tenantId]), [
        'status' => 'posted',
        'discount_type' => 'amount',
        'discount_value' => 0,
        'payment_mode' => 'cash',
        'items' => [[
            'type' => 'product',
            'ref_id' => $fixture['product']->id,
            'qty' => 3,
            'price' => 150,
            'tax_rate' => 0,
            'tax_inclusive' => false,
        ]],
    ]);

    $response->assertRedirect(route('tenant.pos.index', ['tenant' => $tenantId]));

    $stock = InventoryStock::query()
        ->where('branch_id', $fixture['branch']->id)
        ->where('product_id', $fixture['product']->id)
        ->firstOrFail();

    expect((float) $stock->qty_on_hand)->toBe(10.0);
});

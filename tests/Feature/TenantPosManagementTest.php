<?php

declare(strict_types=1);

use App\Enums\BranchStatus;
use App\Enums\RecordStatus;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ServiceCatalog;
use App\Models\Tax;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Stancl\Tenancy\Middleware\InitializeTenancyByPath;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

beforeEach(function (): void {
    Config::set('database.connections.tenant', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]);
    Config::set('database.default', 'tenant');

    Artisan::call('migrate:fresh', [
        '--database' => 'tenant',
        '--path' => database_path('migrations/tenant'),
        '--realpath' => true,
        '--force' => true,
    ]);

    $this->withoutMiddleware([
        InitializeTenancyByPath::class,
        PreventAccessFromCentralDomains::class,
    ]);

    URL::defaults(['tenant' => 'test-tenant-id']);
});

function posTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

/**
 * @return array{current: Branch, secondary: Branch, category: Category, product: Product, service: ServiceCatalog}
 */
function authenticatePosUser(): array
{
    $currentBranch = Branch::query()->create([
        'code' => 'MAIN',
        'name' => 'Main Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $secondaryBranch = Branch::query()->create([
        'code' => 'ALT',
        'name' => 'Alt Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $user = User::query()->create([
        'branch_id' => $currentBranch->id,
        'name' => 'POS User',
        'email' => 'pos.user+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => 'active',
    ]);

    Customer::query()->withoutGlobalScopes()->create([
        'branch_id' => $currentBranch->id,
        'code' => 'CUST-M',
        'name' => 'Main Customer',
        'status' => 'active',
    ]);

    Customer::query()->withoutGlobalScopes()->create([
        'branch_id' => $secondaryBranch->id,
        'code' => 'CUST-A',
        'name' => 'Alt Customer',
        'status' => 'active',
    ]);

    $tax = Tax::query()->create([
        'code' => 'GST',
        'name' => 'GST',
        'rate' => 17,
        'is_inclusive' => false,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $category = Category::query()->create([
        'name' => 'Engine',
        'slug' => 'engine',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $product = Product::query()->create([
        'category_id' => $category->id,
        'default_tax_id' => $tax->id,
        'sku' => 'POS-P-1',
        'barcode' => 'POS-BAR-1',
        'name' => 'Engine Oil',
        'track_stock' => true,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    ProductPrice::query()->withoutGlobalScopes()->create([
        'product_id' => $product->id,
        'branch_id' => $currentBranch->id,
        'cost' => 100,
        'mrp' => 150,
        'retail_price' => 130,
        'wholesale_price' => 120,
        'effective_from' => now(),
    ]);

    $warehouse = Warehouse::query()->withoutGlobalScopes()->create([
        'branch_id' => $currentBranch->id,
        'code' => 'POS-WH-1',
        'name' => 'POS Warehouse',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    InventoryStock::query()->create([
        'branch_id' => $currentBranch->id,
        'product_id' => $product->id,
        'qty_on_hand' => 25,
        'qty_reserved' => 0,
    ]);

    $service = ServiceCatalog::query()->withoutGlobalScopes()->create([
        'branch_id' => $currentBranch->id,
        'default_tax_id' => $tax->id,
        'code' => 'POS-S-1',
        'name' => 'Oil Change',
        'category' => 'Workshop',
        'base_price' => 250,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    ServiceCatalog::query()->withoutGlobalScopes()->create([
        'branch_id' => $secondaryBranch->id,
        'default_tax_id' => $tax->id,
        'code' => 'POS-S-2',
        'name' => 'Alt Service',
        'category' => 'Workshop',
        'base_price' => 300,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $currentBranch->id]);

    return [
        'current' => $currentBranch,
        'secondary' => $secondaryBranch,
        'category' => $category,
        'product' => $product,
        'service' => $service,
    ];
}

it('shows pos index', function (): void {
    authenticatePosUser();

    $response = $this->get(posTenantRoute('pos.index'));

    $response->assertSuccessful();
    $response->assertSee('POS');
});

it('returns scan single mode when exactly one item matches', function (): void {
    authenticatePosUser();

    $response = $this->getJson(posTenantRoute('pos.scan', ['query' => 'POS-BAR-1']));

    $response->assertSuccessful();
    $response->assertJsonPath('mode', 'single');
    $response->assertJsonPath('item.type', 'product');
});

it('returns scan list mode for ambiguous query', function (): void {
    authenticatePosUser();

    $response = $this->getJson(posTenantRoute('pos.scan', ['query' => 'Oil']));

    $response->assertSuccessful();
    $response->assertJsonPath('mode', 'list');
    expect($response->json('items'))->toBeArray();
});

it('returns product catalog by category only', function (): void {
    $fixture = authenticatePosUser();

    $response = $this->getJson(posTenantRoute('pos.catalog', [
        'type' => 'product',
        'category' => $fixture['category']->id,
    ]));

    $response->assertSuccessful();
    expect($response->json('items'))->not->toBeEmpty();
    expect($response->json('items.0.type'))->toBe('product');
});

it('returns service catalog scoped to current branch', function (): void {
    authenticatePosUser();

    $response = $this->getJson(posTenantRoute('pos.catalog', [
        'type' => 'service',
        'category' => 'Workshop',
    ]));

    $response->assertSuccessful();
    $names = collect($response->json('items'))->pluck('name')->all();
    expect($names)->toContain('Oil Change');
    expect($names)->not->toContain('Alt Service');
});

it('validates online payment proof requirement in pos store', function (): void {
    $fixture = authenticatePosUser();

    $response = $this->from(posTenantRoute('pos.index'))
        ->post(posTenantRoute('pos.store'), [
            'status' => 'posted',
            'payment_mode' => 'online',
            'items' => [[
                'type' => 'product',
                'ref_id' => $fixture['product']->id,
                'qty' => 1,
                'price' => 130,
            ]],
        ]);

    $response->assertRedirect(posTenantRoute('pos.index'));
    $response->assertSessionHasErrors(['payment_proof']);
});

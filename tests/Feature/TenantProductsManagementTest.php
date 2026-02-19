<?php

declare(strict_types=1);

use App\Enums\BranchStatus;
use App\Enums\RecordStatus;
use App\Enums\StockMoveType;
use App\Http\Controllers\Tenant\ProductController;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\StockMove;
use App\Models\Tax;
use App\Models\Unit;
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

function productsTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

function authenticateProductUser(): Branch
{
    $branch = Branch::query()->create([
        'code' => 'MAIN',
        'name' => 'Main Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $user = User::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Product User',
        'email' => 'product.user+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => 'active',
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $branch->id]);

    return $branch;
}

it('shows products index', function (): void {
    $branch = authenticateProductUser();

    $product = Product::query()->create([
        'sku' => 'PROD-001',
        'name' => 'Engine Oil',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $warehouse = Warehouse::query()->create([
        'branch_id' => $branch->id,
        'code' => 'WH-1',
        'name' => 'Main Warehouse',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    InventoryStock::query()->create([
        'product_id' => $product->id,
        'branch_id' => $branch->id,
        'qty_on_hand' => 20,
        'qty_reserved' => 0,
        'avg_cost' => 0,
    ]);

    $response = $this->get(productsTenantRoute('products.index'));

    $response->assertSuccessful();
    $response->assertSee('Products');
    $response->assertSee('Engine Oil');
    $response->assertSee('20 /');
});

it('filters products by search', function (): void {
    authenticateProductUser();

    Product::query()->create([
        'sku' => 'PROD-AXL',
        'part_number' => 'AXL-01',
        'name' => 'Axle Kit',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    Product::query()->create([
        'sku' => 'PROD-BRK',
        'part_number' => 'BRK-01',
        'name' => 'Brake Kit',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $response = $this->get(productsTenantRoute('products.index', ['search' => 'AXL']));

    $response->assertSuccessful();
    $response->assertSee('Axle Kit');
    $response->assertDontSee('Brake Kit');
});

it('shows create product page with active taxes and units', function (): void {
    authenticateProductUser();

    Tax::query()->create([
        'code' => 'GST',
        'name' => 'GST',
        'rate' => 17,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    Tax::query()->create([
        'code' => 'OLDGST',
        'name' => 'Old GST',
        'rate' => 5,
        'status' => RecordStatus::INACTIVE->value,
    ]);

    Unit::query()->create([
        'code' => 'PCS',
        'name' => 'Pieces',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    Unit::query()->create([
        'code' => 'OLD',
        'name' => 'Old Unit',
        'status' => RecordStatus::INACTIVE->value,
    ]);

    $response = $this->get(productsTenantRoute('products.create'));

    $response->assertSuccessful();
    $response->assertSee('GST');
    $response->assertDontSee('Old GST');
    $response->assertSee('Pieces');
    $response->assertDontSee('Old Unit');
});

it('stores product with relations and flags', function (): void {
    $branch = authenticateProductUser();

    $category = Category::query()->create([
        'name' => 'Engine',
        'slug' => 'engine',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $brand = Brand::query()->create([
        'name' => 'Bosch',
        'slug' => 'bosch',
        'status' => 'active',
    ]);

    $tax = Tax::query()->create([
        'code' => 'GST',
        'name' => 'GST',
        'rate' => 17,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $unit = Unit::query()->create([
        'code' => 'PCS',
        'name' => 'Pieces',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $response = $this->post(productsTenantRoute('products.store'), [
        'name' => 'Air Filter',
        'category_id' => $category->id,
        'brand_id' => $brand->id,
        'default_tax_id' => $tax->id,
        'default_unit_id' => $unit->id,
        'sku' => 'SKU-AIR-1',
        'part_number' => 'PN-AIR-1',
        'barcode' => 'BC-AIR-1',
        'track_stock' => '1',
        'opening_stock' => '20',
        'status' => RecordStatus::ACTIVE->value,
        'description' => 'Air filter description',
    ]);

    $response->assertRedirect(productsTenantRoute('products.index'));
    $response->assertSessionHas('status', 'Created.');

    $this->assertDatabaseHas('products', [
        'name' => 'Air Filter',
        'category_id' => $category->id,
        'brand_id' => $brand->id,
        'default_tax_id' => $tax->id,
        'default_unit_id' => $unit->id,
        'sku' => 'SKU-AIR-1',
        'part_number' => 'PN-AIR-1',
        'barcode' => 'BC-AIR-1',
        'status' => RecordStatus::ACTIVE->value,
    ], 'tenant');

    $product = Product::query()->where('sku', 'SKU-AIR-1')->firstOrFail();

    $stock = InventoryStock::query()
        ->where('branch_id', $branch->id)
        ->where('product_id', $product->id)
        ->firstOrFail();

    expect((float) $stock->qty_on_hand)->toBe(20.0);

    $move = StockMove::query()
        ->where('branch_id', $branch->id)
        ->where('product_id', $product->id)
        ->firstOrFail();

    expect($move->move_type)->toBe(StockMoveType::OPENING);
    expect((float) $move->qty)->toBe(20.0);
});

it('validates required product fields', function (string $field): void {
    authenticateProductUser();

    $payload = [
        'name' => 'Spark Plug',
        'sku' => 'SKU-SPARK',
        'status' => RecordStatus::ACTIVE->value,
    ];

    unset($payload[$field]);

    $response = $this->from(productsTenantRoute('products.create'))
        ->post(productsTenantRoute('products.store'), $payload);

    $response->assertRedirect(productsTenantRoute('products.create'));
    $response->assertSessionHasErrors([$field]);
})->with([
    'name' => 'name',
    'sku' => 'sku',
    'status' => 'status',
]);

it('validates unique sku part number and barcode', function (): void {
    authenticateProductUser();

    Product::query()->create([
        'sku' => 'SKU-UNI',
        'part_number' => 'PN-UNI',
        'barcode' => 'BC-UNI',
        'name' => 'Original Product',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $response = $this->from(productsTenantRoute('products.create'))
        ->post(productsTenantRoute('products.store'), [
            'name' => 'Duplicate Product',
            'sku' => 'SKU-UNI',
            'part_number' => 'PN-UNI',
            'barcode' => 'BC-UNI',
            'status' => RecordStatus::ACTIVE->value,
        ]);

    $response->assertRedirect(productsTenantRoute('products.create'));
    $response->assertSessionHasErrors(['sku', 'part_number', 'barcode']);
});

it('clamps products pagination limits', function (): void {
    authenticateProductUser();

    $minResponse = $this->get(productsTenantRoute('products.index', ['per_page' => 1]));
    $maxResponse = $this->get(productsTenantRoute('products.index', ['per_page' => 999]));

    expect($minResponse->viewData('items')->perPage())->toBe(5);
    expect($maxResponse->viewData('items')->perPage())->toBe(100);
});

it('allows multiple products when nullable unique fields are omitted', function (): void {
    authenticateProductUser();

    $firstResponse = $this->post(productsTenantRoute('products.store'), [
        'name' => 'No Optional Codes A',
        'sku' => 'SKU-NUL-1',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $secondResponse = $this->post(productsTenantRoute('products.store'), [
        'name' => 'No Optional Codes B',
        'sku' => 'SKU-NUL-2',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $firstResponse->assertRedirect(productsTenantRoute('products.index'));
    $secondResponse->assertRedirect(productsTenantRoute('products.index'));

    $this->assertDatabaseHas('products', ['sku' => 'SKU-NUL-1'], 'tenant');
    $this->assertDatabaseHas('products', ['sku' => 'SKU-NUL-2'], 'tenant');
});

it('applies default stock flag when omitted', function (): void {
    authenticateProductUser();

    $response = $this->post(productsTenantRoute('products.store'), [
        'name' => 'Default Flags Product',
        'sku' => 'SKU-DFLT-1',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $response->assertRedirect(productsTenantRoute('products.index'));

    $product = Product::query()->where('sku', 'SKU-DFLT-1')->firstOrFail();
    expect($product->track_stock)->toBeTrue();
});

it('validates product status and relation id formats', function (): void {
    authenticateProductUser();

    $response = $this->from(productsTenantRoute('products.create'))
        ->post(productsTenantRoute('products.store'), [
            'name' => 'Invalid Fields Product',
            'sku' => 'SKU-INV-1',
            'category_id' => 'not-a-uuid',
            'brand_id' => 'not-a-uuid',
            'default_tax_id' => 'not-a-uuid',
            'default_unit_id' => 'not-a-uuid',
            'status' => 'invalid',
        ]);

    $response->assertRedirect(productsTenantRoute('products.create'));
    $response->assertSessionHasErrors(['category_id', 'brand_id', 'default_tax_id', 'default_unit_id', 'status']);
});

it('shows product details', function (): void {
    $branch = authenticateProductUser();

    $product = Product::query()->create([
        'name' => 'Show Product',
        'sku' => 'SKU-SHOW-1',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    session()->put('tenant.current_branch_id', $branch->id);
    $response = (new ProductController())->show($product);

    expect($response->name())->toBe('tenants.products.show');
    expect($response->getData()['product']->id)->toBe($product->id);
});

it('shows edit product page', function (): void {
    authenticateProductUser();

    $product = Product::query()->create([
        'name' => 'Edit Product',
        'sku' => 'SKU-EDIT-1',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $response = (new ProductController())->edit($product);

    expect($response->name())->toBe('tenants.products.edit');
    expect($response->getData()['product']->id)->toBe($product->id);
});

it('deletes product', function (): void {
    authenticateProductUser();

    $product = Product::query()->create([
        'name' => 'Delete Product',
        'sku' => 'SKU-DEL-1',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $response = (new ProductController())->destroy($product);

    expect($response->getTargetUrl())->toBe(productsTenantRoute('products.index'));
    expect($response->getSession()->get('status'))->toBe('Deleted.');
    $this->assertSoftDeleted('products', ['id' => $product->id], 'tenant');
});

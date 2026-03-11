<?php

declare(strict_types=1);

use App\Enums\BranchStatus;
use App\Enums\RecordStatus;
use App\Enums\SaleStatus;
use App\Models\Branch;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
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

function inventoryTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

/**
 * @return array{branch: Branch, product: Product}
 */
function authenticateInventoryModuleUser(): array
{
    $branch = Branch::query()->create([
        'code' => 'MAIN',
        'name' => 'Main Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $user = User::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Inventory User',
        'email' => 'inventory.user+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => 'active',
    ]);

    $product = Product::query()->create([
        'sku' => 'INV-P-1',
        'name' => 'Engine Oil',
        'track_stock' => true,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    InventoryStock::query()->create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'qty_on_hand' => 30,
        'qty_reserved' => 5,
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $branch->id]);

    return ['branch' => $branch, 'product' => $product];
}

it('shows product history page with selectable products', function (): void {
    authenticateInventoryModuleUser();

    $response = $this->get(inventoryTenantRoute('products.history'));

    $response->assertSuccessful();
    $response->assertSee('Product History');
    $response->assertSee('Select Product');
    $response->assertDontSee('Search Product');
});

it('shows selected product sold quantity in product history', function (): void {
    $context = authenticateInventoryModuleUser();

    $sale = Sale::query()->create([
        'branch_id' => $context['branch']->id,
        'invoice_no' => 'INV-HIS-1',
        'invoice_date' => now()->toDateString(),
        'status' => SaleStatus::POSTED->value,
    ]);

    SaleItem::query()->create([
        'sale_id' => $sale->id,
        'branch_id' => $context['branch']->id,
        'product_id' => $context['product']->id,
        'line_type' => 'product',
        'qty' => 4,
        'unit_price' => 50,
        'line_total' => 200,
    ]);

    $response = $this->get(inventoryTenantRoute('products.history', ['product_id' => $context['product']->id]));

    $response->assertSuccessful();

    expect($response->viewData('summary')['sold_qty'])->toBe(4.0);
    expect($response->viewData('summary')['qty_on_hand'])->toBe(30.0);
});

it('does not expose old stock module routes', function (): void {
    authenticateInventoryModuleUser();

    $this->get('/firm/test-tenant-id/products/stock')->assertNotFound();
    $this->get('/firm/test-tenant-id/products/stock/adjustments')->assertNotFound();
});

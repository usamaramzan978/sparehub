<?php

declare(strict_types=1);

use App\Actions\Tenant\Product\UpdateProductAction;
use App\Enums\BranchStatus;
use App\Enums\RecordStatus;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductPrice;
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

function productPricesTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

function authenticateProductPriceUser(): Branch
{
    $branch = Branch::query()->create([
        'code' => 'MAIN',
        'name' => 'Main Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $user = User::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Price User',
        'email' => 'price.user+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => 'active',
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $branch->id]);

    return $branch;
}

it('stores product prices through product create endpoint', function (): void {
    $branch = authenticateProductPriceUser();

    $response = $this->post(productPricesTenantRoute('products.store'), [
        'name' => 'Priced Product',
        'sku' => 'SKU-PRICE-NEW',
        'status' => RecordStatus::ACTIVE->value,
        'cost' => 100,
        'mrp' => 130,
        'retail_price' => 120,
        'wholesale_price' => 110,
        'effective_from' => now()->format('Y-m-d H:i:s'),
    ]);

    $response->assertRedirect(productPricesTenantRoute('products.index'));

    $product = Product::query()->where('sku', 'SKU-PRICE-NEW')->firstOrFail();

    $this->assertDatabaseHas('product_prices', [
        'product_id' => $product->id,
        'branch_id' => $branch->id,
        'cost' => 100,
        'mrp' => 130,
        'retail_price' => 120,
        'wholesale_price' => 110,
    ], 'tenant');
});

it('updates latest product price when product is updated', function (): void {
    $branch = authenticateProductPriceUser();

    $product = Product::query()->create([
        'sku' => 'SKU-PRICE-UPD',
        'name' => 'Price Update Product',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $latestPrice = ProductPrice::query()->withoutGlobalScopes()->create([
        'product_id' => $product->id,
        'branch_id' => $branch->id,
        'cost' => 50,
        'mrp' => 70,
        'retail_price' => 65,
        'wholesale_price' => 60,
        'effective_from' => now()->subDays(2),
    ]);

    app(UpdateProductAction::class)->handle($product, [
        'name' => 'Price Update Product',
        'sku' => 'SKU-PRICE-UPD',
        'status' => RecordStatus::ACTIVE->value,
        'cost' => 150,
        'mrp' => 190,
        'retail_price' => 180,
        'wholesale_price' => 170,
        'effective_from' => now()->format('Y-m-d H:i:s'),
    ], $branch->id);

    $latestPrice->refresh();

    expect((float) $latestPrice->cost)->toBe(150.0);
    expect((float) $latestPrice->mrp)->toBe(190.0);
    expect((float) $latestPrice->retail_price)->toBe(180.0);
    expect((float) $latestPrice->wholesale_price)->toBe(170.0);

    expect(ProductPrice::query()->withoutGlobalScopes()->where('product_id', $product->id)->count())->toBe(1);
});

it('does not expose standalone product prices frontend route', function (): void {
    authenticateProductPriceUser();

    $this->get('/firm/test-tenant-id/product-prices')->assertNotFound();
});

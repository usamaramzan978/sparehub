<?php

declare(strict_types=1);

use App\Actions\Tenant\ProductPrice\DeleteProductPriceAction;
use App\Actions\Tenant\ProductPrice\EnsureProductPriceInBranchAction;
use App\Enums\BranchStatus;
use App\Enums\RecordStatus;
use App\Http\Controllers\Tenant\ProductPriceController;
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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

/**
 * @return array{current: Branch, secondary: Branch}
 */
function authenticateProductPriceUser(): array
{
    $currentBranch = Branch::query()->create([
        'code' => 'MAIN',
        'name' => 'Main Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $secondaryBranch = Branch::query()->create([
        'code' => 'ALT',
        'name' => 'Alternate Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $user = User::query()->create([
        'branch_id' => $currentBranch->id,
        'name' => 'Price User',
        'email' => 'price.user+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => 'active',
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $currentBranch->id]);

    return [
        'current' => $currentBranch,
        'secondary' => $secondaryBranch,
    ];
}

function makeProduct(string $sku, string $name): Product
{
    return Product::query()->create([
        'sku' => $sku,
        'name' => $name,
        'status' => RecordStatus::ACTIVE->value,
    ]);
}

it('shows product prices index only for current branch', function (): void {
    $branches = authenticateProductPriceUser();

    $productA = makeProduct('SKU-A', 'Product A');
    $productB = makeProduct('SKU-B', 'Product B');

    ProductPrice::query()->withoutGlobalScopes()->create([
        'product_id' => $productA->id,
        'branch_id' => $branches['current']->id,
        'cost' => 100,
        'mrp' => 130,
        'retail_price' => 120,
        'wholesale_price' => 110,
        'effective_from' => now(),
    ]);

    ProductPrice::query()->withoutGlobalScopes()->create([
        'product_id' => $productB->id,
        'branch_id' => $branches['secondary']->id,
        'cost' => 200,
        'mrp' => 230,
        'retail_price' => 220,
        'wholesale_price' => 210,
        'effective_from' => now(),
    ]);

    $response = $this->get(productPricesTenantRoute('product-prices.index'));

    $response->assertSuccessful();

    $pricedProductNames = $response->viewData('items')
        ->getCollection()
        ->pluck('product.name')
        ->all();

    expect($pricedProductNames)->toContain('Product A');
    expect($pricedProductNames)->not->toContain('Product B');

    $response->assertSee('data-ajax-table-search', false);
    $response->assertSee('product-prices-search-form');
    $response->assertSee('product-prices-search-loading');
});

it('filters product prices by product name or sku in current branch', function (): void {
    $branches = authenticateProductPriceUser();

    $productAxle = makeProduct('SKU-AXL', 'Axle Kit');
    $productBrake = makeProduct('SKU-BRK', 'Brake Kit');

    ProductPrice::query()->withoutGlobalScopes()->create([
        'product_id' => $productAxle->id,
        'branch_id' => $branches['current']->id,
        'cost' => 100,
        'mrp' => 130,
        'retail_price' => 120,
        'wholesale_price' => 110,
        'effective_from' => now(),
    ]);

    ProductPrice::query()->withoutGlobalScopes()->create([
        'product_id' => $productBrake->id,
        'branch_id' => $branches['current']->id,
        'cost' => 90,
        'mrp' => 120,
        'retail_price' => 110,
        'wholesale_price' => 100,
        'effective_from' => now(),
    ]);

    $bySkuResponse = $this->get(productPricesTenantRoute('product-prices.index', ['search' => 'AXL']));
    $byNameResponse = $this->get(productPricesTenantRoute('product-prices.index', ['search' => 'Brake']));

    $bySkuResponse->assertSuccessful();
    expect($bySkuResponse->viewData('items')->getCollection()->pluck('product.name')->all())->toContain('Axle Kit');
    expect($bySkuResponse->viewData('items')->getCollection()->pluck('product.name')->all())->not->toContain('Brake Kit');

    $byNameResponse->assertSuccessful();
    expect($byNameResponse->viewData('items')->getCollection()->pluck('product.name')->all())->toContain('Brake Kit');
    expect($byNameResponse->viewData('items')->getCollection()->pluck('product.name')->all())->not->toContain('Axle Kit');
});

it('stores product price with current branch id', function (): void {
    $branches = authenticateProductPriceUser();

    $product = makeProduct('SKU-C', 'Product C');

    $response = $this->post(productPricesTenantRoute('product-prices.store'), [
        'product_id' => $product->id,
        'cost' => 100,
        'mrp' => 150,
        'retail_price' => 140,
        'wholesale_price' => 130,
        'effective_from' => now()->format('Y-m-d H:i:s'),
    ]);

    $response->assertRedirect(productPricesTenantRoute('product-prices.index'));
    $response->assertSessionHas('status', 'Created.');

    $this->assertDatabaseHas('product_prices', [
        'product_id' => $product->id,
        'branch_id' => $branches['current']->id,
        'cost' => 100,
        'mrp' => 150,
        'retail_price' => 140,
        'wholesale_price' => 130,
    ], 'tenant');
});

it('validates required product price fields', function (string $field): void {
    authenticateProductPriceUser();

    $product = makeProduct('SKU-D', 'Product D');

    $payload = [
        'product_id' => $product->id,
        'cost' => 10,
        'mrp' => 12,
        'retail_price' => 11,
        'wholesale_price' => 10,
        'effective_from' => now()->format('Y-m-d H:i:s'),
    ];

    unset($payload[$field]);

    $response = $this->from(productPricesTenantRoute('product-prices.index'))
        ->post(productPricesTenantRoute('product-prices.store'), $payload);

    $response->assertRedirect(productPricesTenantRoute('product-prices.index'));
    $response->assertSessionHasErrors([$field]);
})->with([
    'product_id' => 'product_id',
    'cost' => 'cost',
    'mrp' => 'mrp',
    'retail_price' => 'retail_price',
    'wholesale_price' => 'wholesale_price',
    'effective_from' => 'effective_from',
]);

it('validates numeric floor for product prices', function (): void {
    authenticateProductPriceUser();

    $product = makeProduct('SKU-E', 'Product E');

    $response = $this->from(productPricesTenantRoute('product-prices.index'))
        ->post(productPricesTenantRoute('product-prices.store'), [
            'product_id' => $product->id,
            'cost' => -1,
            'mrp' => -1,
            'retail_price' => -1,
            'wholesale_price' => -1,
            'effective_from' => now()->format('Y-m-d H:i:s'),
        ]);

    $response->assertRedirect(productPricesTenantRoute('product-prices.index'));
    $response->assertSessionHasErrors(['cost', 'mrp', 'retail_price', 'wholesale_price']);
});

it('clamps product prices pagination limits', function (): void {
    authenticateProductPriceUser();

    $minResponse = $this->get(productPricesTenantRoute('product-prices.index', ['per_page' => 1]));
    $maxResponse = $this->get(productPricesTenantRoute('product-prices.index', ['per_page' => 999]));

    expect($minResponse->viewData('items')->perPage())->toBe(5);
    expect($maxResponse->viewData('items')->perPage())->toBe(100);
});

it('validates effective from date format', function (): void {
    authenticateProductPriceUser();
    $product = makeProduct('SKU-BAD-DATE', 'Product Bad Date');

    $response = $this->from(productPricesTenantRoute('product-prices.index'))
        ->post(productPricesTenantRoute('product-prices.store'), [
            'product_id' => $product->id,
            'cost' => 10,
            'mrp' => 12,
            'retail_price' => 11,
            'wholesale_price' => 10,
            'effective_from' => 'not-a-date',
        ]);

    $response->assertRedirect(productPricesTenantRoute('product-prices.index'));
    $response->assertSessionHasErrors(['effective_from']);
});

it('validates product id format', function (): void {
    authenticateProductPriceUser();

    $response = $this->from(productPricesTenantRoute('product-prices.index'))
        ->post(productPricesTenantRoute('product-prices.store'), [
            'product_id' => 'not-a-uuid',
            'cost' => 10,
            'mrp' => 12,
            'retail_price' => 11,
            'wholesale_price' => 10,
            'effective_from' => now()->format('Y-m-d H:i:s'),
        ]);

    $response->assertRedirect(productPricesTenantRoute('product-prices.index'));
    $response->assertSessionHasErrors(['product_id']);
});

it('shows product price details for current branch', function (): void {
    $branches = authenticateProductPriceUser();
    $product = makeProduct('SKU-SHOW', 'Product Show');

    $price = ProductPrice::query()->withoutGlobalScopes()->create([
        'product_id' => $product->id,
        'branch_id' => $branches['current']->id,
        'cost' => 10,
        'mrp' => 12,
        'retail_price' => 11,
        'wholesale_price' => 10,
        'effective_from' => now(),
    ]);

    session()->put('tenant.current_branch_id', $branches['current']->id);
    $response = (new ProductPriceController())->show($price, new EnsureProductPriceInBranchAction());

    expect($response->name())->toBe('tenants.product-prices.show');
    expect($response->getData()['productPrice']->id)->toBe($price->id);
});

it('deletes product price', function (): void {
    $branches = authenticateProductPriceUser();
    $product = makeProduct('SKU-DEL', 'Product Delete');

    $price = ProductPrice::query()->withoutGlobalScopes()->create([
        'product_id' => $product->id,
        'branch_id' => $branches['current']->id,
        'cost' => 10,
        'mrp' => 12,
        'retail_price' => 11,
        'wholesale_price' => 10,
        'effective_from' => now(),
    ]);

    session()->put('tenant.current_branch_id', $branches['current']->id);
    $response = (new ProductPriceController())->destroy(
        $price,
        new DeleteProductPriceAction(),
        new EnsureProductPriceInBranchAction()
    );

    expect($response->getTargetUrl())->toBe(productPricesTenantRoute('product-prices.index'));
    $this->assertDatabaseMissing('product_prices', ['id' => $price->id], 'tenant');
});

it('throws not found when accessing price from another branch', function (): void {
    $branches = authenticateProductPriceUser();
    $product = makeProduct('SKU-OUT', 'Product Out');

    $foreignPrice = ProductPrice::query()->withoutGlobalScopes()->create([
        'product_id' => $product->id,
        'branch_id' => $branches['secondary']->id,
        'cost' => 10,
        'mrp' => 12,
        'retail_price' => 11,
        'wholesale_price' => 10,
        'effective_from' => now(),
    ]);

    $this->expectException(NotFoundHttpException::class);
    (new ProductPriceController())->show($foreignPrice, new EnsureProductPriceInBranchAction());
});

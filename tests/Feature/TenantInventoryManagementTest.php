<?php

declare(strict_types=1);

use App\Enums\BranchStatus;
use App\Enums\RecordStatus;
use App\Enums\StockMoveType;
use App\Enums\UserStatus;
use App\Models\Branch;
use App\Models\Category;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\StockMove;
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

function inventoryTenantRoute(array $parameters = []): string
{
    return route('tenant.products.stock.index', ['tenant' => 'test-tenant-id', ...$parameters]);
}

function inventoryAdjustmentTenantRoute(): string
{
    return route('tenant.products.stock.adjustments.store', ['tenant' => 'test-tenant-id']);
}

function inventoryAdjustmentPageTenantRoute(): string
{
    return route('tenant.products.stock.adjustments', ['tenant' => 'test-tenant-id']);
}

/**
 * @return array{branch: Branch, product: Product, inactive: Product, warehouse: Warehouse}
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
        'status' => UserStatus::ACTIVE->value,
    ]);

    $category = Category::query()->create([
        'name' => 'Engine',
        'slug' => 'engine',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $product = Product::query()->create([
        'category_id' => $category->id,
        'sku' => 'INV-P-1',
        'name' => 'Engine Oil',
        'track_stock' => true,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $inactive = Product::query()->create([
        'category_id' => $category->id,
        'sku' => 'INV-P-2',
        'name' => 'Old Part',
        'track_stock' => true,
        'status' => RecordStatus::INACTIVE->value,
    ]);

    $warehouse = Warehouse::query()->withoutGlobalScopes()->create([
        'branch_id' => $branch->id,
        'code' => 'INV-WH-1',
        'name' => 'Inventory Warehouse',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    ProductPrice::query()->withoutGlobalScopes()->create([
        'product_id' => $product->id,
        'branch_id' => $branch->id,
        'cost' => 100,
        'mrp' => 150,
        'retail_price' => 130,
        'wholesale_price' => 120,
        'effective_from' => now(),
    ]);

    InventoryStock::query()->create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'qty_on_hand' => 30,
        'qty_reserved' => 5,
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $branch->id]);

    return ['branch' => $branch, 'product' => $product, 'inactive' => $inactive, 'warehouse' => $warehouse];
}

it('shows inventory dashboard summary', function (): void {
    authenticateInventoryModuleUser();

    $response = $this->get(inventoryTenantRoute());

    $response->assertSuccessful();

    $summary = $response->viewData('summary');

    expect($summary['products_count'])->toBe(1);
    expect($summary['qty_on_hand_total'])->toBe(30.0);
    expect($summary['qty_available_total'])->toBe(25.0);
});

it('filters inventory by search query', function (): void {
    authenticateInventoryModuleUser();

    $response = $this->get(inventoryTenantRoute(['search' => 'Engine Oil']));

    $response->assertSuccessful();

    $tree = $response->viewData('inventoryTree');

    expect($tree->first()['products_count'])->toBe(1);
});

it('shows inactive products only when requested', function (): void {
    authenticateInventoryModuleUser();

    $defaultResponse = $this->get(inventoryTenantRoute());
    $withInactiveResponse = $this->get(inventoryTenantRoute(['show_inactive' => 1]));

    $defaultSummary = $defaultResponse->viewData('summary');
    $withInactiveSummary = $withInactiveResponse->viewData('summary');

    expect($defaultSummary['products_count'])->toBe(1);
    expect($withInactiveSummary['products_count'])->toBe(2);
});

it('shows stock adjustment page', function (): void {
    authenticateInventoryModuleUser();

    $response = $this->get(inventoryAdjustmentPageTenantRoute());

    $response->assertSuccessful();
    $response->assertSee('Stock Adjustment');
    $response->assertSee('Apply Adjustment');
    $response->assertSee('Engine Oil (INV-P-1)');
});

it('allows stock adjustment from inventory page', function (): void {
    $context = authenticateInventoryModuleUser();

    $response = $this->post(inventoryAdjustmentTenantRoute(), [
        'product_id' => $context['product']->id,
        'action' => 'in',
        'qty' => 5,
        'remarks' => 'Manual correction',
    ]);

    $response->assertRedirect(inventoryAdjustmentPageTenantRoute());
    $response->assertSessionHas('status', 'Stock adjusted.');

    $stock = InventoryStock::query()
        ->where('product_id', $context['product']->id)
        ->where('branch_id', $context['branch']->id)
        ->firstOrFail();

    expect((float) $stock->qty_on_hand)->toBe(35.0);

    $move = StockMove::query()
        ->where('product_id', $context['product']->id)
        ->where('branch_id', $context['branch']->id)
        ->latest('created_at')
        ->firstOrFail();

    expect($move->move_type)->toBe(StockMoveType::ADJUSTMENT_IN);
    expect((float) $move->qty)->toBe(5.0);
});

it('prevents stock out greater than available stock', function (): void {
    $context = authenticateInventoryModuleUser();

    $response = $this->from(inventoryTenantRoute())
        ->post(inventoryAdjustmentTenantRoute(), [
            'product_id' => $context['product']->id,
            'action' => 'out',
            'qty' => 100,
        ]);

    $response->assertRedirect(inventoryTenantRoute());
    $response->assertSessionHasErrors(['qty' => 'Stock out quantity exceeds available stock.']);
});

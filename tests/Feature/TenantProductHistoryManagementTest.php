<?php

declare(strict_types=1);

use App\Enums\BranchStatus;
use App\Enums\RecordStatus;
use App\Enums\SaleStatus;
use App\Enums\StockMoveType;
use App\Models\Branch;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMove;
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

function productHistoryTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

function authenticateProductHistoryUser(): Branch
{
    $branch = Branch::query()->create([
        'code' => 'MAIN',
        'name' => 'Main Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $user = User::query()->create([
        'branch_id' => $branch->id,
        'name' => 'History User',
        'email' => 'history.user+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => 'active',
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $branch->id]);

    return $branch;
}

it('shows product history page and sidebar link', function (): void {
    authenticateProductHistoryUser();

    $historyResponse = $this->get(productHistoryTenantRoute('products.history'));
    $historyResponse->assertSuccessful();
    $historyResponse->assertSee('Product History');

    $productsResponse = $this->get(productHistoryTenantRoute('products.index'));
    $productsResponse->assertSuccessful();
    $productsResponse->assertSee('History');
});

it('shows selected product history summary using sales and stock moves', function (): void {
    $branch = authenticateProductHistoryUser();

    $product = Product::query()->create([
        'sku' => 'HIS-001',
        'name' => 'History Product',
        'status' => RecordStatus::ACTIVE->value,
        'track_stock' => true,
    ]);

    InventoryStock::query()->create([
        'product_id' => $product->id,
        'branch_id' => $branch->id,
        'qty_on_hand' => 17,
        'qty_reserved' => 2,
        'avg_cost' => 0,
    ]);

    $postedSale = Sale::query()->create([
        'branch_id' => $branch->id,
        'invoice_no' => 'INV-1001',
        'invoice_date' => now()->toDateString(),
        'status' => SaleStatus::POSTED->value,
    ]);

    $draftSale = Sale::query()->create([
        'branch_id' => $branch->id,
        'invoice_no' => 'INV-1002',
        'invoice_date' => now()->toDateString(),
        'status' => SaleStatus::DRAFT->value,
    ]);

    SaleItem::query()->create([
        'sale_id' => $postedSale->id,
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'line_type' => 'product',
        'qty' => 3,
        'unit_price' => 100,
        'line_total' => 300,
    ]);

    SaleItem::query()->create([
        'sale_id' => $postedSale->id,
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'line_type' => 'product',
        'qty' => 2,
        'unit_price' => 120,
        'line_total' => 240,
    ]);

    SaleItem::query()->create([
        'sale_id' => $draftSale->id,
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'line_type' => 'product',
        'qty' => 10,
        'unit_price' => 10,
        'line_total' => 100,
    ]);

    StockMove::query()->withoutGlobalScopes()->create([
        'product_id' => $product->id,
        'branch_id' => $branch->id,
        'move_type' => StockMoveType::OPENING->value,
        'qty' => 20,
        'unit_cost' => 0,
        'total_cost' => 0,
        'occurred_at' => now()->subDays(10),
    ]);

    StockMove::query()->withoutGlobalScopes()->create([
        'product_id' => $product->id,
        'branch_id' => $branch->id,
        'move_type' => StockMoveType::PURCHASE->value,
        'qty' => 6,
        'unit_cost' => 0,
        'total_cost' => 0,
        'occurred_at' => now()->subDays(8),
    ]);

    StockMove::query()->withoutGlobalScopes()->create([
        'product_id' => $product->id,
        'branch_id' => $branch->id,
        'move_type' => StockMoveType::ADJUSTMENT_OUT->value,
        'qty' => 1,
        'unit_cost' => 0,
        'total_cost' => 0,
        'occurred_at' => now()->subDays(6),
    ]);

    $response = $this->get(productHistoryTenantRoute('products.history', ['product_id' => $product->id]));

    $response->assertSuccessful();
    $response->assertSee('History Product');

    $summary = $response->viewData('summary');

    expect($summary['sales_count'])->toBe(1);
    expect($summary['sold_qty'])->toBe(5.0);
    expect($summary['sales_amount'])->toBe(540.0);
    expect($summary['opening_qty'])->toBe(20.0);
    expect($summary['purchased_qty'])->toBe(6.0);
    expect($summary['adjusted_out_qty'])->toBe(1.0);
    expect($summary['qty_on_hand'])->toBe(17.0);
    expect($summary['qty_reserved'])->toBe(2.0);
});

<?php

declare(strict_types=1);

use App\Enums\BranchStatus;
use App\Enums\InvoiceType;
use App\Enums\SaleLineType;
use App\Enums\SaleStatus;
use App\Enums\UserStatus;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
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

function salesTreeTenantRoute(array $parameters = []): string
{
    return route('tenant.sales-tree.index', ['tenant' => 'test-tenant-id', ...$parameters]);
}

function authenticateSalesTreeModuleUser(): array
{
    $branch = Branch::query()->create([
        'code' => 'MAIN',
        'name' => 'Main Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $user = User::query()->create([
        'branch_id' => $branch->id,
        'name' => 'SalesTree User',
        'email' => 'sales.tree.user+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    $customer = Customer::query()->withoutGlobalScopes()->create([
        'branch_id' => $branch->id,
        'code' => 'ST-C-1',
        'name' => 'Tree Customer',
        'status' => 'active',
    ]);

    $category = Category::query()->create([
        'name' => 'Engine',
        'slug' => 'engine',
        'status' => 'active',
    ]);

    $product = Product::query()->create([
        'category_id' => $category->id,
        'sku' => 'ST-P-1',
        'name' => 'Tree Product',
        'track_stock' => true,
        'status' => 'active',
    ]);

    $sale = Sale::query()->withoutGlobalScopes()->create([
        'branch_id' => $branch->id,
        'customer_id' => $customer->id,
        'created_by' => $user->id,
        'invoice_no' => 'ST-S-1',
        'invoice_date' => now()->toDateString(),
        'status' => SaleStatus::POSTED->value,
        'invoice_type' => InvoiceType::PRODUCT->value,
        'grand_total' => 300,
        'paid_total' => 100,
        'balance_due' => 200,
    ]);

    SaleItem::query()->withoutGlobalScopes()->create([
        'sale_id' => $sale->id,
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'line_type' => SaleLineType::PRODUCT->value,
        'qty' => 2,
        'unit_price' => 150,
        'line_total' => 300,
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $branch->id]);

    return ['customer' => $customer];
}

it('shows sales tree grouped data and summary', function (): void {
    authenticateSalesTreeModuleUser();

    $response = $this->get(salesTreeTenantRoute());

    $response->assertSuccessful();

    $summary = $response->viewData('summary');
    expect($summary['customers_count'])->toBe(1);
    expect($summary['invoices_count'])->toBe(1);
    expect($summary['grand_total'])->toBe(300.0);
});

it('filters sales tree by customer', function (): void {
    $fixture = authenticateSalesTreeModuleUser();

    $response = $this->get(salesTreeTenantRoute([
        'customer_id' => $fixture['customer']->id,
    ]));

    $response->assertSuccessful();
    expect($response->viewData('summary')['invoices_count'])->toBe(1);
});

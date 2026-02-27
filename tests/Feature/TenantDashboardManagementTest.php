<?php

declare(strict_types=1);

use App\Enums\BranchStatus;
use App\Enums\CustomerStatus;
use App\Enums\InvoiceType;
use App\Enums\JobCardStatus;
use App\Enums\PaymentMethodType;
use App\Enums\PurchaseStatus;
use App\Enums\RecordStatus;
use App\Enums\SaleStatus;
use App\Enums\UserStatus;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\InventoryStock;
use App\Models\JobCard;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorPayment;
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

function dashboardTenantRoute(array $parameters = []): string
{
    return route('tenant.dashboard', ['tenant' => 'test-tenant-id', ...$parameters]);
}

function authenticateDashboardUser(): void
{
    $branch = Branch::query()->create([
        'code' => 'MAIN',
        'name' => 'Main Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $user = User::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Dashboard User',
        'email' => 'dashboard.user+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    $vendor = Vendor::query()->withoutGlobalScopes()->create([
        'branch_id' => $branch->id,
        'code' => 'DB-V-1',
        'name' => 'Dashboard Vendor',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $sale = Sale::query()->withoutGlobalScopes()->create([
        'branch_id' => $branch->id,
        'created_by' => $user->id,
        'invoice_no' => 'DB-S-1',
        'invoice_date' => now()->toDateString(),
        'status' => SaleStatus::POSTED->value,
        'invoice_type' => InvoiceType::PRODUCT->value,
        'grand_total' => 500,
        'paid_total' => 300,
        'balance_due' => 200,
    ]);

    $purchase = Purchase::query()->withoutGlobalScopes()->create([
        'branch_id' => $branch->id,
        'vendor_id' => $vendor->id,
        'created_by' => $user->id,
        'purchase_no' => 'DB-P-1',
        'purchase_date' => now()->toDateString(),
        'status' => PurchaseStatus::POSTED->value,
        'grand_total' => 200,
        'paid_total' => 100,
        'balance_due' => 100,
    ]);

    SalePayment::query()->withoutGlobalScopes()->create([
        'sale_id' => $sale->id,
        'branch_id' => $branch->id,
        'received_by' => $user->id,
        'payment_method' => PaymentMethodType::CASH->value,
        'amount' => 300,
        'paid_at' => now(),
    ]);

    VendorPayment::query()->withoutGlobalScopes()->create([
        'branch_id' => $branch->id,
        'vendor_id' => $vendor->id,
        'purchase_id' => $purchase->id,
        'created_by' => $user->id,
        'payment_no' => 'DB-VP-1',
        'payment_method' => PaymentMethodType::BANK->value,
        'amount' => 100,
        'paid_at' => now(),
    ]);

    $category = Category::query()->create([
        'name' => 'Dashboard',
        'slug' => 'dashboard',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $product = Product::query()->create([
        'category_id' => $category->id,
        'sku' => 'DB-PROD-1',
        'name' => 'Low Stock Product',
        'track_stock' => true,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $topStockProduct = Product::query()->create([
        'category_id' => $category->id,
        'sku' => 'DB-PROD-2',
        'name' => 'High Stock Product',
        'track_stock' => true,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    InventoryStock::query()->create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'qty_on_hand' => 3,
        'qty_reserved' => 0,
    ]);

    InventoryStock::query()->create([
        'branch_id' => $branch->id,
        'product_id' => $topStockProduct->id,
        'qty_on_hand' => 20,
        'qty_reserved' => 1,
    ]);

    $customer = Customer::query()->withoutGlobalScopes()->create([
        'branch_id' => $branch->id,
        'code' => 'DB-CUST-1',
        'name' => 'Dashboard Customer',
        'status' => CustomerStatus::ACTIVE->value,
    ]);

    JobCard::query()->withoutGlobalScopes()->create([
        'branch_id' => $branch->id,
        'customer_id' => $customer->id,
        'created_by' => $user->id,
        'job_no' => 'DB-JC-1',
        'job_date' => now()->toDateString(),
        'status' => JobCardStatus::IN_PROGRESS->value,
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $branch->id]);
}

it('shows dashboard summary and chart data', function (): void {
    authenticateDashboardUser();
    $today = now()->toDateString();

    $response = $this->get(dashboardTenantRoute([
        'date_from' => $today,
        'date_to' => $today,
    ]));

    $response->assertSuccessful();

    $summary = $response->viewData('summary');
    $chartData = $response->viewData('chartData');
    $tenantHealth = $response->viewData('tenantHealth');

    expect($summary)->toHaveKeys(['sales_total', 'purchases_total', 'cashflow_net']);
    expect((float) $summary['cashflow_net'])->toBe((float) $summary['sale_payments_total'] - (float) $summary['vendor_payments_total']);
    expect($chartData['trend_labels'])->not->toBeEmpty();
    expect($tenantHealth)->toMatchArray([
        'low_stock_count' => 1,
        'unpaid_vendors_count' => 1,
        'open_job_cards_count' => 1,
        'failed_logins_count' => 0,
    ]);

    $response->assertSee('Top Stock');
    $response->assertSee('Tenant Health');
    $response->assertSee('Low Stock Items');
    $response->assertSee('Unpaid Vendors');
    $response->assertSee('Open Job Cards');
    $response->assertSee('Failed Logins');
    $response->assertSee('High Stock Product');
    $response->assertSee('20');
});

it('accepts date range filter parsing', function (): void {
    authenticateDashboardUser();

    $today = now()->toDateString();

    $response = $this->get(dashboardTenantRoute([
        'date_range' => $today.' to '.$today,
    ]));

    $response->assertSuccessful();

    $dateRange = $response->viewData('dateRange');
    expect($dateRange['date_from'])->toBe($today);
    expect($dateRange['date_to'])->toBe($today);
});

it('accepts human readable dashboard date range values', function (): void {
    authenticateDashboardUser();

    $today = now()->toDateString();
    $humanDate = now()->format('F, d Y');

    $response = $this->get(dashboardTenantRoute([
        'date_range' => $humanDate.' to '.$humanDate,
    ]));

    $response->assertSuccessful();

    $dateRange = $response->viewData('dateRange');
    expect($dateRange['date_from'])->toBe($today);
    expect($dateRange['date_to'])->toBe($today);
});

it('validates dashboard date filters', function (): void {
    authenticateDashboardUser();

    $response = $this->from(dashboardTenantRoute())
        ->get(dashboardTenantRoute([
            'date_from' => now()->toDateString(),
            'date_to' => now()->subDay()->toDateString(),
        ]));

    $response->assertRedirect(dashboardTenantRoute());
    $response->assertSessionHasErrors(['date_to']);
});

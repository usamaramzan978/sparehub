<?php

declare(strict_types=1);

use App\Enums\BranchStatus;
use App\Enums\InvoiceType;
use App\Enums\PaymentMethodType;
use App\Enums\PurchaseStatus;
use App\Enums\SaleStatus;
use App\Enums\UserStatus;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorPayment;
use Illuminate\Support\Carbon;
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

function reportsTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

/**
 * @return array{branch: Branch, customer: Customer, vendor: Vendor, sale: Sale, purchase: Purchase}
 */
function authenticateReportsModuleUser(): array
{
    $branch = Branch::query()->create([
        'code' => 'MAIN',
        'name' => 'Main Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $user = User::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Reports User',
        'email' => 'reports.user+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    $customer = Customer::query()->withoutGlobalScopes()->create([
        'branch_id' => $branch->id,
        'code' => 'REP-C-1',
        'name' => 'Report Customer',
        'status' => 'active',
    ]);

    $vendor = Vendor::query()->withoutGlobalScopes()->create([
        'branch_id' => $branch->id,
        'code' => 'REP-V-1',
        'name' => 'Report Vendor',
        'status' => 'active',
    ]);

    $sale = Sale::query()->withoutGlobalScopes()->create([
        'branch_id' => $branch->id,
        'customer_id' => $customer->id,
        'created_by' => $user->id,
        'invoice_no' => 'REP-S-1',
        'invoice_date' => now()->toDateString(),
        'status' => SaleStatus::POSTED->value,
        'invoice_type' => InvoiceType::PRODUCT->value,
        'grand_total' => 500,
        'paid_total' => 100,
        'balance_due' => 400,
    ]);

    $purchase = Purchase::query()->withoutGlobalScopes()->create([
        'branch_id' => $branch->id,
        'vendor_id' => $vendor->id,
        'created_by' => $user->id,
        'purchase_no' => 'REP-P-1',
        'purchase_date' => now()->toDateString(),
        'status' => PurchaseStatus::POSTED->value,
        'grand_total' => 600,
        'paid_total' => 200,
        'balance_due' => 400,
    ]);

    SalePayment::query()->withoutGlobalScopes()->create([
        'sale_id' => $sale->id,
        'branch_id' => $branch->id,
        'received_by' => $user->id,
        'payment_method' => PaymentMethodType::CASH->value,
        'amount' => 100,
        'paid_at' => now(),
    ]);

    VendorPayment::query()->withoutGlobalScopes()->create([
        'branch_id' => $branch->id,
        'vendor_id' => $vendor->id,
        'purchase_id' => $purchase->id,
        'created_by' => $user->id,
        'payment_no' => 'REP-VP-1',
        'payment_method' => PaymentMethodType::BANK->value,
        'amount' => 200,
        'paid_at' => now(),
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $branch->id]);

    return [
        'branch' => $branch,
        'customer' => $customer,
        'vendor' => $vendor,
        'sale' => $sale,
        'purchase' => $purchase,
    ];
}

it('shows reports index with summary metrics', function (): void {
    authenticateReportsModuleUser();

    $response = $this->get(reportsTenantRoute('reports.index'));

    $response->assertSuccessful();

    $summary = $response->viewData('summary');

    expect($summary['sales_count'])->toBe(1);
    expect($summary['purchases_count'])->toBe(1);
    expect($summary['sale_payments_total'])->toBe(100.0);
    expect($summary['vendor_payments_total'])->toBe(200.0);
});

it('filters reports by customer and vendor', function (): void {
    $fixture = authenticateReportsModuleUser();

    $response = $this->get(reportsTenantRoute('reports.index', [
        'customer_id' => $fixture['customer']->id,
        'vendor_id' => $fixture['vendor']->id,
    ]));

    $response->assertSuccessful();

    expect($response->viewData('sales')->total())->toBe(1);
    expect($response->viewData('purchases')->total())->toBe(1);
});

it('shows dedicated report pages', function (string $routeName): void {
    authenticateReportsModuleUser();

    $response = $this->get(reportsTenantRoute($routeName));

    $response->assertSuccessful();
})->with([
    'overview' => 'reports.overview',
    'sales' => 'reports.sales',
    'purchases' => 'reports.purchases',
    'sale payments' => 'reports.sale-payments',
    'vendor payments' => 'reports.vendor-payments',
    'receivables' => 'reports.receivables',
    'payables' => 'reports.payables',
]);

it('exports detailed reports pdf', function (): void {
    authenticateReportsModuleUser();

    $response = $this->get(reportsTenantRoute('reports.export.pdf'));

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'application/pdf');
});

it('exports page specific reports pdf', function (string $routeName): void {
    authenticateReportsModuleUser();

    $response = $this->get(reportsTenantRoute($routeName));

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'application/pdf');
})->with([
    'sales pdf' => 'reports.export.sales-pdf',
    'purchases pdf' => 'reports.export.purchases-pdf',
    'sale payments pdf' => 'reports.export.sale-payments-pdf',
    'vendor payments pdf' => 'reports.export.vendor-payments-pdf',
    'receivables pdf' => 'reports.export.receivables-pdf',
    'payables pdf' => 'reports.export.payables-pdf',
]);

it('exports summary reports pdf', function (): void {
    authenticateReportsModuleUser();

    $response = $this->get(reportsTenantRoute('reports.export.summary-pdf'));

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'application/pdf');
});

it('applies full-day tenant date range boundaries for payment datetime filters', function (): void {
    $fixture = authenticateReportsModuleUser();
    $branch = $fixture['branch'];
    $sale = $fixture['sale'];
    $purchase = $fixture['purchase'];

    $user = auth('user')->user();

    SalePayment::query()->withoutGlobalScopes()->create([
        'sale_id' => $sale->id,
        'branch_id' => $branch->id,
        'received_by' => $user?->id,
        'payment_method' => PaymentMethodType::CASH->value,
        'amount' => 50,
        'paid_at' => Carbon::parse('2026-03-01 00:00:00'),
    ]);

    SalePayment::query()->withoutGlobalScopes()->create([
        'sale_id' => $sale->id,
        'branch_id' => $branch->id,
        'received_by' => $user?->id,
        'payment_method' => PaymentMethodType::CASH->value,
        'amount' => 75,
        'paid_at' => Carbon::parse('2026-03-01 23:59:59'),
    ]);

    SalePayment::query()->withoutGlobalScopes()->create([
        'sale_id' => $sale->id,
        'branch_id' => $branch->id,
        'received_by' => $user?->id,
        'payment_method' => PaymentMethodType::CASH->value,
        'amount' => 90,
        'paid_at' => Carbon::parse('2026-03-02 00:00:00'),
    ]);

    VendorPayment::query()->withoutGlobalScopes()->create([
        'branch_id' => $branch->id,
        'vendor_id' => $fixture['vendor']->id,
        'purchase_id' => $purchase->id,
        'created_by' => $user?->id,
        'payment_no' => 'REP-VP-EDGE-1',
        'payment_method' => PaymentMethodType::BANK->value,
        'amount' => 45,
        'paid_at' => Carbon::parse('2026-03-01 00:00:00'),
    ]);

    VendorPayment::query()->withoutGlobalScopes()->create([
        'branch_id' => $branch->id,
        'vendor_id' => $fixture['vendor']->id,
        'purchase_id' => $purchase->id,
        'created_by' => $user?->id,
        'payment_no' => 'REP-VP-EDGE-2',
        'payment_method' => PaymentMethodType::BANK->value,
        'amount' => 55,
        'paid_at' => Carbon::parse('2026-03-01 23:59:59'),
    ]);

    VendorPayment::query()->withoutGlobalScopes()->create([
        'branch_id' => $branch->id,
        'vendor_id' => $fixture['vendor']->id,
        'purchase_id' => $purchase->id,
        'created_by' => $user?->id,
        'payment_no' => 'REP-VP-OUT',
        'payment_method' => PaymentMethodType::BANK->value,
        'amount' => 65,
        'paid_at' => Carbon::parse('2026-03-02 00:00:00'),
    ]);

    $response = $this->get(reportsTenantRoute('reports.index', [
        'date_from' => '2026-03-01',
        'date_to' => '2026-03-01',
    ]));

    $response->assertSuccessful();

    expect($response->viewData('salePayments')->total())->toBe(2);
    expect($response->viewData('vendorPayments')->total())->toBe(2);
});

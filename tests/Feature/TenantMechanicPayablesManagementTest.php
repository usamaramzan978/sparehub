<?php

declare(strict_types=1);

use App\Enums\BranchStatus;
use App\Enums\InvoiceType;
use App\Enums\RecordStatus;
use App\Enums\SaleLineType;
use App\Enums\SaleStatus;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\ServiceCatalog;
use App\Models\Tax;
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

function mechanicPayablesTenantRoute(array $parameters = []): string
{
    return route('tenant.mechanic-payables.index', ['tenant' => 'test-tenant-id', ...$parameters]);
}

it('shows mechanic payable summary and lines', function (): void {
    $branch = Branch::query()->create([
        'code' => 'MAIN',
        'name' => 'Main Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $mechanic = User::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Mechanic A',
        'email' => 'mechanic.a+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => 'active',
    ]);

    $cashier = User::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Cashier A',
        'email' => 'cashier.a+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => 'active',
    ]);

    $customer = Customer::query()->withoutGlobalScopes()->create([
        'branch_id' => $branch->id,
        'code' => 'CUST-MP-1',
        'name' => 'Customer MP',
        'status' => RecordStatus::ACTIVE->value,
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

    Product::query()->create([
        'category_id' => $category->id,
        'default_tax_id' => $tax->id,
        'sku' => 'MP-P-1',
        'name' => 'Oil',
        'track_stock' => true,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $service = ServiceCatalog::query()->withoutGlobalScopes()->create([
        'branch_id' => $branch->id,
        'default_tax_id' => $tax->id,
        'code' => 'MP-S-1',
        'name' => 'Oil Change',
        'category' => 'Workshop',
        'base_price' => 100,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $sale = Sale::query()->withoutGlobalScopes()->create([
        'branch_id' => $branch->id,
        'customer_id' => $customer->id,
        'created_by' => $cashier->id,
        'invoice_no' => 'MP-INV-1',
        'invoice_date' => now()->toDateString(),
        'status' => SaleStatus::POSTED->value,
        'invoice_type' => InvoiceType::SERVICE->value,
    ]);

    SaleItem::query()->withoutGlobalScopes()->create([
        'sale_id' => $sale->id,
        'branch_id' => $branch->id,
        'service_catalog_id' => $service->id,
        'mechanic_id' => $mechanic->id,
        'line_type' => SaleLineType::SERVICE->value,
        'description' => 'Oil change labour',
        'qty' => 1,
        'unit_price' => 150,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'mechanic_charge' => 100,
        'line_total' => 150,
    ]);

    test()->actingAs($cashier, 'user');
    test()->withSession(['tenant.current_branch_id' => $branch->id]);

    $response = $this->get(mechanicPayablesTenantRoute());

    $response->assertSuccessful();
    $response->assertSee('data-ajax-sort-link', false);
    $response->assertSee('Mechanic A');
    $response->assertSee('Oil change labour');

    $summary = $response->viewData('summary');
    expect($summary['lines_count'])->toBe(1);
    expect((float) $summary['total_payable'])->toBe(100.0);
});

it('sorts mechanic payable lines by payable amount ascending and descending', function (): void {
    $branch = Branch::query()->create([
        'code' => 'MAIN',
        'name' => 'Main Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $mechanic = User::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Mechanic Sort',
        'email' => 'mechanic.sort+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => 'active',
    ]);

    $cashier = User::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Cashier Sort',
        'email' => 'cashier.sort+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => 'active',
    ]);

    $customer = Customer::query()->withoutGlobalScopes()->create([
        'branch_id' => $branch->id,
        'code' => 'CUST-MP-SORT',
        'name' => 'Customer Sort',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $tax = Tax::query()->create([
        'code' => 'GST',
        'name' => 'GST',
        'rate' => 17,
        'is_inclusive' => false,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $service = ServiceCatalog::query()->withoutGlobalScopes()->create([
        'branch_id' => $branch->id,
        'default_tax_id' => $tax->id,
        'code' => 'MP-S-SORT',
        'name' => 'Service Sort',
        'category' => 'Workshop',
        'base_price' => 100,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $sale = Sale::query()->withoutGlobalScopes()->create([
        'branch_id' => $branch->id,
        'customer_id' => $customer->id,
        'created_by' => $cashier->id,
        'invoice_no' => 'MP-INV-SORT-1',
        'invoice_date' => now()->toDateString(),
        'status' => SaleStatus::POSTED->value,
        'invoice_type' => InvoiceType::SERVICE->value,
    ]);

    SaleItem::query()->withoutGlobalScopes()->create([
        'sale_id' => $sale->id,
        'branch_id' => $branch->id,
        'service_catalog_id' => $service->id,
        'mechanic_id' => $mechanic->id,
        'line_type' => SaleLineType::SERVICE->value,
        'description' => 'Low Payable',
        'qty' => 1,
        'unit_price' => 100,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'mechanic_charge' => 50,
        'line_total' => 100,
    ]);

    SaleItem::query()->withoutGlobalScopes()->create([
        'sale_id' => $sale->id,
        'branch_id' => $branch->id,
        'service_catalog_id' => $service->id,
        'mechanic_id' => $mechanic->id,
        'line_type' => SaleLineType::SERVICE->value,
        'description' => 'High Payable',
        'qty' => 1,
        'unit_price' => 150,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'mechanic_charge' => 150,
        'line_total' => 150,
    ]);

    test()->actingAs($cashier, 'user');
    test()->withSession(['tenant.current_branch_id' => $branch->id]);

    $ascending = $this->get(mechanicPayablesTenantRoute([
        'sort_by' => 'mechanic_charge',
        'sort_direction' => 'asc',
    ]));

    $descending = $this->get(mechanicPayablesTenantRoute([
        'sort_by' => 'mechanic_charge',
        'sort_direction' => 'desc',
    ]));

    $ascendingCharges = $ascending->viewData('items')->pluck('mechanic_charge')->map(fn ($value): float => (float) $value)->values()->all();
    $descendingCharges = $descending->viewData('items')->pluck('mechanic_charge')->map(fn ($value): float => (float) $value)->values()->all();

    expect(array_search(50.0, $ascendingCharges, true))->toBeLessThan(array_search(150.0, $ascendingCharges, true));
    expect(array_search(50.0, $descendingCharges, true))->toBeGreaterThan(array_search(150.0, $descendingCharges, true));
});

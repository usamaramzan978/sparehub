<?php

declare(strict_types=1);

use App\Actions\Tenant\Sale\DeleteSaleAction;
use App\Actions\Tenant\Sale\EnsureSaleInBranchAction;
use App\Enums\BranchStatus;
use App\Enums\InvoiceType;
use App\Enums\RecordStatus;
use App\Enums\SaleLineType;
use App\Enums\SaleStatus;
use App\Http\Controllers\Tenant\SaleController;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\Sale;
use App\Models\ServiceCatalog;
use App\Models\Tax;
use App\Models\User;
use App\Models\Warehouse;
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

function salesTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

/**
 * @return array{current: Branch, secondary: Branch, warehouse: Warehouse, user: User, customer: Customer, product: Product, service: ServiceCatalog}
 */
function authenticateSalesUser(): array
{
    $currentBranch = Branch::query()->create([
        'code' => 'MAIN',
        'name' => 'Main Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $secondaryBranch = Branch::query()->create([
        'code' => 'ALT',
        'name' => 'Alt Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $warehouse = Warehouse::query()->create([
        'branch_id' => $currentBranch->id,
        'code' => 'MAIN-WH',
        'name' => 'Main Warehouse',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $currentBranch->update(['warehouse_id' => $warehouse->id]);

    $user = User::query()->create([
        'branch_id' => $currentBranch->id,
        'name' => 'Sales User',
        'email' => 'sales.user+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => 'active',
    ]);

    $customer = Customer::query()->withoutGlobalScopes()->create([
        'branch_id' => $currentBranch->id,
        'code' => 'CUST-S-1',
        'name' => 'Sales Customer',
        'status' => 'active',
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

    $product = Product::query()->create([
        'category_id' => $category->id,
        'default_tax_id' => $tax->id,
        'sku' => 'SALE-P-1',
        'name' => 'Engine Oil',
        'track_stock' => true,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $service = ServiceCatalog::query()->withoutGlobalScopes()->create([
        'branch_id' => $currentBranch->id,
        'default_tax_id' => $tax->id,
        'code' => 'SALE-S-1',
        'name' => 'Wheel Alignment',
        'category' => 'Workshop',
        'base_price' => 500,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $currentBranch->id]);

    return [
        'current' => $currentBranch,
        'secondary' => $secondaryBranch,
        'warehouse' => $warehouse,
        'user' => $user,
        'customer' => $customer,
        'product' => $product,
        'service' => $service,
    ];
}

it('shows sales index for current branch only', function (): void {
    $fixture = authenticateSalesUser();

    Sale::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'customer_id' => $fixture['customer']->id,
        'created_by' => $fixture['user']->id,
        'invoice_no' => 'INV-MAIN-1',
        'invoice_date' => now()->toDateString(),
        'status' => SaleStatus::POSTED->value,
        'invoice_type' => InvoiceType::PRODUCT->value,
    ]);

    Sale::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['secondary']->id,
        'created_by' => $fixture['user']->id,
        'invoice_no' => 'INV-ALT-1',
        'invoice_date' => now()->toDateString(),
        'status' => SaleStatus::POSTED->value,
        'invoice_type' => InvoiceType::PRODUCT->value,
    ]);

    $response = $this->get(salesTenantRoute('sales.index'));

    $response->assertSuccessful();
    $response->assertSee('data-ajax-table-search', false);
    $response->assertSee('id="sales-search-form"', false);

    expect($response->viewData('items')->total())->toBe(1);
    expect($response->viewData('items')->items()[0]->invoice_no)->toBe('INV-MAIN-1');
});

it('renders status badges on sales index', function (): void {
    $fixture = authenticateSalesUser();

    Sale::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'customer_id' => $fixture['customer']->id,
        'created_by' => $fixture['user']->id,
        'invoice_no' => 'INV-BADGE-POSTED',
        'invoice_date' => now()->toDateString(),
        'status' => SaleStatus::POSTED->value,
        'invoice_type' => InvoiceType::PRODUCT->value,
    ]);

    Sale::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'customer_id' => $fixture['customer']->id,
        'created_by' => $fixture['user']->id,
        'invoice_no' => 'INV-BADGE-HOLD',
        'invoice_date' => now()->toDateString(),
        'status' => SaleStatus::HOLD->value,
        'invoice_type' => InvoiceType::SERVICE->value,
    ]);

    $response = $this->get(salesTenantRoute('sales.index'));

    $response->assertSuccessful();
    $response->assertSee('INV-BADGE-POSTED');
    $response->assertSee('INV-BADGE-HOLD');
    $response->assertSee('Posted');
    $response->assertSee('Hold');
    $response->assertSee('Product');
    $response->assertSee('Service');
});

it('clamps sales pagination limits', function (): void {
    authenticateSalesUser();

    $minResponse = $this->get(salesTenantRoute('sales.index', ['per_page' => 1]));
    $maxResponse = $this->get(salesTenantRoute('sales.index', ['per_page' => 999]));

    expect($minResponse->viewData('items')->perPage())->toBe(5);
    expect($maxResponse->viewData('items')->perPage())->toBe(100);
});

it('stores sale and syncs totals from items', function (): void {
    $fixture = authenticateSalesUser();

    InventoryStock::query()->create([
        'product_id' => $fixture['product']->id,
        'branch_id' => $fixture['current']->id,
        'qty_on_hand' => 10,
        'qty_reserved' => 0,
        'avg_cost' => 0,
    ]);

    $response = $this->post(salesTenantRoute('sales.store'), [
        'customer_id' => $fixture['customer']->id,
        'invoice_no' => 'INV-STORE-1',
        'invoice_date' => now()->toDateString(),
        'status' => SaleStatus::POSTED->value,
        'invoice_type' => InvoiceType::MIXED->value,
        'notes' => 'Created from test',
        'items' => [
            [
                'line_type' => SaleLineType::PRODUCT->value,
                'product_id' => $fixture['product']->id,
                'qty' => 2,
                'unit_price' => 100,
                'discount_amount' => 10,
                'tax_amount' => 5,
            ],
            [
                'line_type' => SaleLineType::SERVICE->value,
                'service_catalog_id' => $fixture['service']->id,
                'mechanic_id' => $fixture['user']->id,
                'mechanic_charge' => 100,
                'qty' => 1,
                'unit_price' => 200,
                'discount_amount' => 0,
                'tax_amount' => 20,
            ],
        ],
    ]);

    $response->assertRedirect(salesTenantRoute('sales.index'));

    $sale = Sale::query()->where('invoice_no', 'INV-STORE-1')->firstOrFail();

    expect((float) $sale->sub_total)->toBe(400.0);
    expect((float) $sale->discount_total)->toBe(10.0);
    expect((float) $sale->tax_total)->toBe(25.0);
    expect((float) $sale->grand_total)->toBe(415.0);
    expect((float) $sale->balance_due)->toBe(415.0);
    expect($sale->items()->count())->toBe(2);
    expect((float) $sale->items()->where('line_type', SaleLineType::SERVICE->value)->value('mechanic_charge'))->toBe(100.0);
    expect((string) $sale->items()->where('line_type', SaleLineType::SERVICE->value)->value('mechanic_id'))->toBe($fixture['user']->id);

    $stock = InventoryStock::query()
        ->where('branch_id', $fixture['current']->id)
        ->where('product_id', $fixture['product']->id)
        ->firstOrFail();

    expect((float) $stock->qty_on_hand)->toBe(8.0);
});

it('does not decrement stock for non tracked products in sales flow', function (): void {
    $fixture = authenticateSalesUser();
    $fixture['product']->update(['track_stock' => false]);

    InventoryStock::query()->create([
        'product_id' => $fixture['product']->id,
        'branch_id' => $fixture['current']->id,
        'qty_on_hand' => 10,
        'qty_reserved' => 0,
        'avg_cost' => 0,
    ]);

    $response = $this->post(salesTenantRoute('sales.store'), [
        'customer_id' => $fixture['customer']->id,
        'invoice_no' => 'INV-NONTRACK-1',
        'invoice_date' => now()->toDateString(),
        'status' => SaleStatus::POSTED->value,
        'invoice_type' => InvoiceType::PRODUCT->value,
        'items' => [
            [
                'line_type' => SaleLineType::PRODUCT->value,
                'product_id' => $fixture['product']->id,
                'qty' => 2,
                'unit_price' => 100,
            ],
        ],
    ]);

    $response->assertRedirect(salesTenantRoute('sales.index'));

    $stock = InventoryStock::query()
        ->where('branch_id', $fixture['current']->id)
        ->where('product_id', $fixture['product']->id)
        ->firstOrFail();

    expect((float) $stock->qty_on_hand)->toBe(10.0);
});

it('validates required service id for service line type', function (): void {
    authenticateSalesUser();

    $response = $this->from(salesTenantRoute('sales.create'))
        ->post(salesTenantRoute('sales.store'), [
            'invoice_no' => 'INV-INVALID-1',
            'invoice_date' => now()->toDateString(),
            'status' => SaleStatus::POSTED->value,
            'invoice_type' => InvoiceType::SERVICE->value,
            'items' => [
                [
                    'line_type' => SaleLineType::SERVICE->value,
                    'qty' => 1,
                    'unit_price' => 150,
                ],
            ],
        ]);

    $response->assertRedirect(salesTenantRoute('sales.create'));
    $response->assertSessionHasErrors(['items.0.service_catalog_id']);
});

it('deletes sale', function (): void {
    $fixture = authenticateSalesUser();

    $sale = Sale::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'created_by' => $fixture['user']->id,
        'invoice_no' => 'INV-DEL-1',
        'invoice_date' => now()->toDateString(),
        'status' => SaleStatus::POSTED->value,
        'invoice_type' => InvoiceType::PRODUCT->value,
    ]);

    session()->put('tenant.current_branch_id', $fixture['current']->id);
    $response = (new SaleController())->destroy(
        $sale,
        app(DeleteSaleAction::class),
        new EnsureSaleInBranchAction()
    );

    expect($response->getTargetUrl())->toBe(salesTenantRoute('sales.index'));
    expect($response->getSession()->get('status'))->toBe('Deleted.');
    $this->assertSoftDeleted('sales', ['id' => $sale->id], 'tenant');
});

it('throws not found when showing sale outside current branch', function (): void {
    $fixture = authenticateSalesUser();

    $foreignSale = Sale::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['secondary']->id,
        'created_by' => $fixture['user']->id,
        'invoice_no' => 'INV-ALT-404',
        'invoice_date' => now()->toDateString(),
        'status' => SaleStatus::POSTED->value,
        'invoice_type' => InvoiceType::PRODUCT->value,
    ]);

    $this->expectException(NotFoundHttpException::class);
    (new SaleController())->show($foreignSale, new EnsureSaleInBranchAction());
});

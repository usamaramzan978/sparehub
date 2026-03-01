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
use App\Models\JobCard;
use App\Models\JobCardPart;
use App\Models\JobCardService;
use App\Models\Product;
use App\Models\Sale;
use App\Models\ServiceCatalog;
use App\Models\Tax;
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

function salesTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

/**
 * @return array{current: Branch, secondary: Branch, user: User, customer: Customer, product: Product, service: ServiceCatalog}
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
        'base_price' => 500,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $currentBranch->id]);

    return [
        'current' => $currentBranch,
        'secondary' => $secondaryBranch,
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
    $response->assertSee('data-ajax-sort-link', false);
    $response->assertSee('id="sales-search-form"', false);

    expect($response->viewData('items')->total())->toBe(1);
    expect($response->viewData('items')->items()[0]->invoice_no)->toBe('INV-MAIN-1');
});

it('sorts sales invoices by invoice number ascending and descending', function (): void {
    $fixture = authenticateSalesUser();

    Sale::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'customer_id' => $fixture['customer']->id,
        'created_by' => $fixture['user']->id,
        'invoice_no' => 'INV-SORT-A',
        'invoice_date' => now()->toDateString(),
        'status' => SaleStatus::POSTED->value,
        'invoice_type' => InvoiceType::PRODUCT->value,
    ]);

    Sale::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'customer_id' => $fixture['customer']->id,
        'created_by' => $fixture['user']->id,
        'invoice_no' => 'INV-SORT-Z',
        'invoice_date' => now()->toDateString(),
        'status' => SaleStatus::POSTED->value,
        'invoice_type' => InvoiceType::PRODUCT->value,
    ]);

    $ascending = $this->get(salesTenantRoute('sales.index', [
        'sort_by' => 'invoice_no',
        'sort_direction' => 'asc',
    ]));

    $descending = $this->get(salesTenantRoute('sales.index', [
        'sort_by' => 'invoice_no',
        'sort_direction' => 'desc',
    ]));

    $ascendingInvoices = $ascending->viewData('items')->pluck('invoice_no')->values()->all();
    $descendingInvoices = $descending->viewData('items')->pluck('invoice_no')->values()->all();

    expect(array_search('INV-SORT-A', $ascendingInvoices, true))->toBeLessThan(array_search('INV-SORT-Z', $ascendingInvoices, true));
    expect(array_search('INV-SORT-A', $descendingInvoices, true))->toBeGreaterThan(array_search('INV-SORT-Z', $descendingInvoices, true));
});

it('filters sales by payment status', function (): void {
    $fixture = authenticateSalesUser();

    Sale::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'customer_id' => $fixture['customer']->id,
        'created_by' => $fixture['user']->id,
        'invoice_no' => 'INV-FILTER-PAID',
        'invoice_date' => now()->toDateString(),
        'status' => SaleStatus::POSTED->value,
        'invoice_type' => InvoiceType::PRODUCT->value,
        'grand_total' => 100,
        'paid_total' => 100,
        'balance_due' => 0,
    ]);

    Sale::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'customer_id' => $fixture['customer']->id,
        'created_by' => $fixture['user']->id,
        'invoice_no' => 'INV-FILTER-PARTIAL',
        'invoice_date' => now()->toDateString(),
        'status' => SaleStatus::POSTED->value,
        'invoice_type' => InvoiceType::PRODUCT->value,
        'grand_total' => 100,
        'paid_total' => 40,
        'balance_due' => 60,
    ]);

    Sale::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'customer_id' => $fixture['customer']->id,
        'created_by' => $fixture['user']->id,
        'invoice_no' => 'INV-FILTER-UNPAID',
        'invoice_date' => now()->toDateString(),
        'status' => SaleStatus::POSTED->value,
        'invoice_type' => InvoiceType::PRODUCT->value,
        'grand_total' => 100,
        'paid_total' => 0,
        'balance_due' => 100,
    ]);

    $paidResponse = $this->get(salesTenantRoute('sales.index', ['payment_status' => 'paid']));
    $partialResponse = $this->get(salesTenantRoute('sales.index', ['payment_status' => 'partial']));
    $unpaidResponse = $this->get(salesTenantRoute('sales.index', ['payment_status' => 'unpaid']));

    expect($paidResponse->viewData('items')->pluck('invoice_no')->all())->toContain('INV-FILTER-PAID');
    expect($paidResponse->viewData('items')->pluck('invoice_no')->all())->not->toContain('INV-FILTER-PARTIAL');
    expect($partialResponse->viewData('items')->pluck('invoice_no')->all())->toContain('INV-FILTER-PARTIAL');
    expect($partialResponse->viewData('items')->pluck('invoice_no')->all())->not->toContain('INV-FILTER-UNPAID');
    expect($unpaidResponse->viewData('items')->pluck('invoice_no')->all())->toContain('INV-FILTER-UNPAID');
    expect($unpaidResponse->viewData('items')->pluck('invoice_no')->all())->not->toContain('INV-FILTER-PAID');
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

it('renders payment badges on sales index', function (): void {
    $fixture = authenticateSalesUser();

    Sale::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'customer_id' => $fixture['customer']->id,
        'created_by' => $fixture['user']->id,
        'invoice_no' => 'INV-PAY-PAID',
        'invoice_date' => now()->toDateString(),
        'status' => SaleStatus::POSTED->value,
        'invoice_type' => InvoiceType::PRODUCT->value,
        'grand_total' => 100,
        'paid_total' => 100,
        'balance_due' => 0,
    ]);

    Sale::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'customer_id' => $fixture['customer']->id,
        'created_by' => $fixture['user']->id,
        'invoice_no' => 'INV-PAY-PARTIAL',
        'invoice_date' => now()->toDateString(),
        'status' => SaleStatus::POSTED->value,
        'invoice_type' => InvoiceType::PRODUCT->value,
        'grand_total' => 100,
        'paid_total' => 40,
        'balance_due' => 60,
    ]);

    Sale::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'customer_id' => $fixture['customer']->id,
        'created_by' => $fixture['user']->id,
        'invoice_no' => 'INV-PAY-UNPAID',
        'invoice_date' => now()->toDateString(),
        'status' => SaleStatus::POSTED->value,
        'invoice_type' => InvoiceType::PRODUCT->value,
        'grand_total' => 100,
        'paid_total' => 0,
        'balance_due' => 100,
    ]);

    $response = $this->get(salesTenantRoute('sales.index'));

    $response->assertSuccessful();
    $response->assertSee('Payment');
    $response->assertSee('INV-PAY-PAID');
    $response->assertSee('INV-PAY-PARTIAL');
    $response->assertSee('INV-PAY-UNPAID');
    $response->assertSee('Paid');
    $response->assertSee('Partial');
    $response->assertSee('Unpaid');
});

it('clamps sales pagination limits', function (): void {
    authenticateSalesUser();

    $minResponse = $this->get(salesTenantRoute('sales.index', ['per_page' => 1]));
    $maxResponse = $this->get(salesTenantRoute('sales.index', ['per_page' => 999]));

    expect($minResponse->viewData('items')->perPage())->toBe(5);
    expect($maxResponse->viewData('items')->perPage())->toBe(100);
});

it('renders simplified invoice item columns on sales create form', function (): void {
    authenticateSalesUser();

    $response = $this->get(salesTenantRoute('sales.create'));

    $response->assertSuccessful();
    $response->assertSee('Invoice Items');
    $response->assertSee('Type');
    $response->assertSee('Product / Service');
    $response->assertSee('Qty');
    $response->assertSee('Retail Price');
    $response->assertSee('Discount');
    $response->assertSee('Line Total');
    $response->assertDontSee('Mechanic Payable');
    $response->assertDontSee('Mechanic');
    $response->assertDontSee('Description');
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
            ],
            [
                'line_type' => SaleLineType::SERVICE->value,
                'service_catalog_id' => $fixture['service']->id,
                'qty' => 1,
                'unit_price' => 200,
                'discount_amount' => 0,
            ],
        ],
    ]);

    $response->assertRedirect(salesTenantRoute('sales.index'));

    $sale = Sale::query()->where('invoice_no', 'INV-STORE-1')->firstOrFail();

    expect((float) $sale->sub_total)->toBe(400.0);
    expect((float) $sale->discount_total)->toBe(10.0);
    expect((float) $sale->tax_total)->toBe(0.0);
    expect((float) $sale->grand_total)->toBe(390.0);
    expect((float) $sale->balance_due)->toBe(390.0);
    expect($sale->items()->count())->toBe(2);

    $stock = InventoryStock::query()
        ->where('branch_id', $fixture['current']->id)
        ->where('product_id', $fixture['product']->id)
        ->firstOrFail();

    expect((float) $stock->qty_on_hand)->toBe(8.0);
});

it('returns job card services and parts as sale items payload', function (): void {
    $fixture = authenticateSalesUser();

    $jobCard = JobCard::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'customer_id' => $fixture['customer']->id,
        'created_by' => $fixture['user']->id,
        'job_no' => 'JC-INV-1',
        'job_date' => now()->toDateString(),
        'status' => 'in_progress',
        'total_visits' => 1,
    ]);

    $jobCardService = JobCardService::query()->create([
        'job_card_id' => $jobCard->id,
        'service_catalog_id' => $fixture['service']->id,
        'technician_id' => $fixture['user']->id,
        'service_name' => 'Wheel Alignment',
        'qty' => 2,
        'rate' => 400,
        'line_total' => 800,
        'status' => 'in_progress',
    ]);

    JobCardPart::query()->create([
        'job_card_id' => $jobCard->id,
        'product_id' => $fixture['product']->id,
        'qty' => 1,
        'unit_price' => 900,
        'line_total' => 900,
    ]);

    session()->put('tenant.current_branch_id', $fixture['current']->id);
    $response = (new SaleController())->jobCardItems($jobCard->id);
    $payload = $response->getData(true);

    expect($response->status())->toBe(200);
    expect($payload['customer_id'])->toBe($fixture['customer']->id);
    expect($payload['items'])->toHaveCount(2);
    expect($payload['items'][0]['line_type'])->toBe(SaleLineType::SERVICE->value);
    expect($payload['items'][0]['service_catalog_id'])->toBe($fixture['service']->id);
    expect($payload['items'][0]['job_card_service_id'])->toBe($jobCardService->id);
    expect($payload['items'][1]['line_type'])->toBe(SaleLineType::PRODUCT->value);
    expect($payload['items'][1]['product_id'])->toBe($fixture['product']->id);
});

it('returns empty job card payload for a different branch', function (): void {
    $fixture = authenticateSalesUser();

    $secondaryCustomer = Customer::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['secondary']->id,
        'code' => 'CUST-S-2',
        'name' => 'Foreign Customer',
        'status' => 'active',
    ]);

    $foreignJobCard = JobCard::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['secondary']->id,
        'customer_id' => $secondaryCustomer->id,
        'created_by' => $fixture['user']->id,
        'job_no' => 'JC-FOREIGN-1',
        'job_date' => now()->toDateString(),
        'status' => 'new',
        'total_visits' => 1,
    ]);

    session()->put('tenant.current_branch_id', $fixture['current']->id);
    $response = (new SaleController())->jobCardItems($foreignJobCard->id);
    $payload = $response->getData(true);

    expect($response->status())->toBe(200);
    expect($payload)->toBe([
        'customer_id' => null,
        'items' => [],
    ]);
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

it('renders payment status badge on sale details page', function (): void {
    $fixture = authenticateSalesUser();

    $sale = Sale::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'customer_id' => $fixture['customer']->id,
        'created_by' => $fixture['user']->id,
        'invoice_no' => 'INV-SHOW-PARTIAL',
        'invoice_date' => now()->toDateString(),
        'status' => SaleStatus::POSTED->value,
        'invoice_type' => InvoiceType::PRODUCT->value,
        'grand_total' => 500,
        'paid_total' => 200,
        'balance_due' => 300,
    ]);

    session()->put('tenant.current_branch_id', $fixture['current']->id);
    $response = (new SaleController())->show($sale, new EnsureSaleInBranchAction());

    expect($response->name())->toBe('tenants.sales.show');
    expect($response->getData()['sale']->invoice_no)->toBe('INV-SHOW-PARTIAL');
    expect($response->getData()['paymentStatus']['label'])->toBe('Partial');
});

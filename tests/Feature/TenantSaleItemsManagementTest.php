<?php

declare(strict_types=1);

use App\Actions\Tenant\SaleItem\DeleteSaleItemAction;
use App\Actions\Tenant\SaleItem\EnsureSaleItemInBranchAction;
use App\Enums\BranchStatus;
use App\Enums\InvoiceType;
use App\Enums\RecordStatus;
use App\Enums\SaleLineType;
use App\Enums\SaleStatus;
use App\Http\Controllers\Tenant\SaleItemController;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
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

function saleItemsTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

/**
 * @return array{current: Branch, secondary: Branch, warehouse: Warehouse, sale: Sale, product: Product, service: ServiceCatalog, user: User}
 */
function authenticateSaleItemsUser(): array
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
        'name' => 'SaleItem User',
        'email' => 'sale.item.user+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => 'active',
    ]);

    $customer = Customer::query()->withoutGlobalScopes()->create([
        'branch_id' => $currentBranch->id,
        'code' => 'SI-CUST-1',
        'name' => 'Sale Item Customer',
        'status' => 'active',
    ]);

    $sale = Sale::query()->withoutGlobalScopes()->create([
        'branch_id' => $currentBranch->id,
        'customer_id' => $customer->id,
        'created_by' => $user->id,
        'invoice_no' => 'SI-INV-1',
        'invoice_date' => now()->toDateString(),
        'status' => SaleStatus::POSTED->value,
        'invoice_type' => InvoiceType::PRODUCT->value,
        'sub_total' => 0,
        'discount_total' => 0,
        'tax_total' => 0,
        'grand_total' => 0,
        'paid_total' => 0,
        'balance_due' => 0,
    ]);

    $tax = Tax::query()->create([
        'code' => 'GST',
        'name' => 'GST',
        'rate' => 17,
        'is_inclusive' => false,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $category = Category::query()->create([
        'name' => 'Filters',
        'slug' => 'filters',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $product = Product::query()->create([
        'category_id' => $category->id,
        'default_tax_id' => $tax->id,
        'sku' => 'SI-P-1',
        'name' => 'Air Filter',
        'track_stock' => true,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $service = ServiceCatalog::query()->withoutGlobalScopes()->create([
        'branch_id' => $currentBranch->id,
        'default_tax_id' => $tax->id,
        'code' => 'SI-S-1',
        'name' => 'Filter Installation',
        'category' => 'Workshop',
        'base_price' => 150,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $currentBranch->id]);

    return [
        'current' => $currentBranch,
        'secondary' => $secondaryBranch,
        'warehouse' => $warehouse,
        'sale' => $sale,
        'product' => $product,
        'service' => $service,
        'user' => $user,
    ];
}

it('shows sale items index for current branch only', function (): void {
    $fixture = authenticateSaleItemsUser();

    SaleItem::query()->withoutGlobalScopes()->create([
        'sale_id' => $fixture['sale']->id,
        'branch_id' => $fixture['current']->id,
        'line_type' => SaleLineType::PRODUCT->value,
        'product_id' => $fixture['product']->id,
        'qty' => 1,
        'unit_price' => 200,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'line_total' => 200,
    ]);

    $foreignSale = Sale::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['secondary']->id,
        'created_by' => $fixture['user']->id,
        'invoice_no' => 'SI-INV-2',
        'invoice_date' => now()->toDateString(),
        'status' => SaleStatus::POSTED->value,
        'invoice_type' => InvoiceType::PRODUCT->value,
    ]);

    SaleItem::query()->withoutGlobalScopes()->create([
        'sale_id' => $foreignSale->id,
        'branch_id' => $fixture['secondary']->id,
        'line_type' => SaleLineType::SERVICE->value,
        'service_catalog_id' => $fixture['service']->id,
        'qty' => 1,
        'unit_price' => 100,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'line_total' => 100,
    ]);

    $response = $this->get(saleItemsTenantRoute('sale-items.index'));

    $response->assertSuccessful();
    $response->assertSee('data-ajax-table-search', false);
    $response->assertSee('data-ajax-sort-link', false);
    $response->assertSee('id="sale-items-search-form"', false);

    expect($response->viewData('items')->total())->toBe(1);
});

it('sorts sale items by line total ascending and descending', function (): void {
    $fixture = authenticateSaleItemsUser();

    SaleItem::query()->withoutGlobalScopes()->create([
        'sale_id' => $fixture['sale']->id,
        'branch_id' => $fixture['current']->id,
        'line_type' => SaleLineType::PRODUCT->value,
        'product_id' => $fixture['product']->id,
        'qty' => 1,
        'unit_price' => 10,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'line_total' => 10,
    ]);

    SaleItem::query()->withoutGlobalScopes()->create([
        'sale_id' => $fixture['sale']->id,
        'branch_id' => $fixture['current']->id,
        'line_type' => SaleLineType::PRODUCT->value,
        'product_id' => $fixture['product']->id,
        'qty' => 1,
        'unit_price' => 100,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'line_total' => 100,
    ]);

    $ascending = $this->get(saleItemsTenantRoute('sale-items.index', [
        'sort_by' => 'line_total',
        'sort_direction' => 'asc',
    ]));

    $descending = $this->get(saleItemsTenantRoute('sale-items.index', [
        'sort_by' => 'line_total',
        'sort_direction' => 'desc',
    ]));

    $ascendingTotals = $ascending->viewData('items')->pluck('line_total')->map(fn ($value): float => (float) $value)->values()->all();
    $descendingTotals = $descending->viewData('items')->pluck('line_total')->map(fn ($value): float => (float) $value)->values()->all();

    expect(array_search(10.0, $ascendingTotals, true))->toBeLessThan(array_search(100.0, $ascendingTotals, true));
    expect(array_search(10.0, $descendingTotals, true))->toBeGreaterThan(array_search(100.0, $descendingTotals, true));
});

it('clamps sale items pagination limits', function (): void {
    authenticateSaleItemsUser();

    $minResponse = $this->get(saleItemsTenantRoute('sale-items.index', ['per_page' => 1]));
    $maxResponse = $this->get(saleItemsTenantRoute('sale-items.index', ['per_page' => 999]));

    expect($minResponse->viewData('items')->perPage())->toBe(5);
    expect($maxResponse->viewData('items')->perPage())->toBe(100);
});

it('searches sale items by invoice and description', function (): void {
    $fixture = authenticateSaleItemsUser();

    $saleTwo = Sale::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'created_by' => $fixture['user']->id,
        'invoice_no' => 'SI-INV-SEARCH-2',
        'invoice_date' => now()->toDateString(),
        'status' => SaleStatus::POSTED->value,
        'invoice_type' => InvoiceType::PRODUCT->value,
        'sub_total' => 0,
        'discount_total' => 0,
        'tax_total' => 0,
        'grand_total' => 0,
        'paid_total' => 0,
        'balance_due' => 0,
    ]);

    SaleItem::query()->withoutGlobalScopes()->create([
        'sale_id' => $fixture['sale']->id,
        'branch_id' => $fixture['current']->id,
        'line_type' => SaleLineType::PRODUCT->value,
        'product_id' => $fixture['product']->id,
        'description' => 'Needle Bearing',
        'qty' => 1,
        'unit_price' => 100,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'line_total' => 100,
    ]);

    SaleItem::query()->withoutGlobalScopes()->create([
        'sale_id' => $saleTwo->id,
        'branch_id' => $fixture['current']->id,
        'line_type' => SaleLineType::PRODUCT->value,
        'product_id' => $fixture['product']->id,
        'description' => 'Brake Pad',
        'qty' => 1,
        'unit_price' => 150,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'line_total' => 150,
    ]);

    $byDescription = $this->get(saleItemsTenantRoute('sale-items.index', ['search' => 'Needle']));
    $byInvoice = $this->get(saleItemsTenantRoute('sale-items.index', ['search' => 'SI-INV-SEARCH-2']));

    expect($byDescription->viewData('items')->total())->toBe(1);
    expect($byInvoice->viewData('items')->total())->toBe(1);
});

it('stores sale item and recalculates parent sale totals', function (): void {
    $fixture = authenticateSaleItemsUser();

    InventoryStock::query()->create([
        'product_id' => $fixture['product']->id,
        'branch_id' => $fixture['current']->id,
        'qty_on_hand' => 10,
        'qty_reserved' => 0,
        'avg_cost' => 0,
    ]);

    $response = $this->post(saleItemsTenantRoute('sale-items.store'), [
        'sale_id' => $fixture['sale']->id,
        'line_type' => SaleLineType::PRODUCT->value,
        'product_id' => $fixture['product']->id,
        'qty' => 2,
        'unit_price' => 100,
        'discount_amount' => 10,
        'tax_amount' => 20,
    ]);

    $response->assertRedirect(saleItemsTenantRoute('sale-items.index'));

    $fixture['sale']->refresh();
    expect((float) $fixture['sale']->sub_total)->toBe(200.0);
    expect((float) $fixture['sale']->discount_total)->toBe(10.0);
    expect((float) $fixture['sale']->tax_total)->toBe(20.0);
    expect((float) $fixture['sale']->grand_total)->toBe(210.0);
    expect((float) $fixture['sale']->balance_due)->toBe(210.0);

    $stock = InventoryStock::query()
        ->where('branch_id', $fixture['current']->id)
        ->where('product_id', $fixture['product']->id)
        ->firstOrFail();

    expect((float) $stock->qty_on_hand)->toBe(8.0);
});

it('stores service sale item with mechanic payable', function (): void {
    $fixture = authenticateSaleItemsUser();

    $response = $this->post(saleItemsTenantRoute('sale-items.store'), [
        'sale_id' => $fixture['sale']->id,
        'line_type' => SaleLineType::SERVICE->value,
        'service_catalog_id' => $fixture['service']->id,
        'mechanic_id' => $fixture['user']->id,
        'mechanic_charge' => 75,
        'qty' => 1,
        'unit_price' => 200,
        'discount_amount' => 0,
        'tax_amount' => 20,
    ]);

    $response->assertRedirect(saleItemsTenantRoute('sale-items.index'));

    $item = SaleItem::query()
        ->where('sale_id', $fixture['sale']->id)
        ->where('line_type', SaleLineType::SERVICE->value)
        ->firstOrFail();

    expect((string) $item->mechanic_id)->toBe($fixture['user']->id);
    expect((float) $item->mechanic_charge)->toBe(75.0);
});

it('validates required sale id for sale item creation', function (): void {
    authenticateSaleItemsUser();

    $response = $this->from(saleItemsTenantRoute('sale-items.create'))
        ->post(saleItemsTenantRoute('sale-items.store'), [
            'line_type' => SaleLineType::PRODUCT->value,
            'qty' => 1,
            'unit_price' => 100,
        ]);

    $response->assertRedirect(saleItemsTenantRoute('sale-items.create'));
    $response->assertSessionHasErrors(['sale_id']);
});

it('deletes sale item and recalculates parent sale totals', function (): void {
    $fixture = authenticateSaleItemsUser();

    InventoryStock::query()->create([
        'product_id' => $fixture['product']->id,
        'branch_id' => $fixture['current']->id,
        'qty_on_hand' => 8,
        'qty_reserved' => 0,
        'avg_cost' => 0,
    ]);

    $item = SaleItem::query()->withoutGlobalScopes()->create([
        'sale_id' => $fixture['sale']->id,
        'branch_id' => $fixture['current']->id,
        'line_type' => SaleLineType::PRODUCT->value,
        'product_id' => $fixture['product']->id,
        'qty' => 2,
        'unit_price' => 100,
        'discount_amount' => 20,
        'tax_amount' => 10,
        'line_total' => 190,
    ]);

    $fixture['sale']->update([
        'sub_total' => 200,
        'discount_total' => 20,
        'tax_total' => 10,
        'grand_total' => 190,
        'balance_due' => 190,
    ]);

    session()->put('tenant.current_branch_id', $fixture['current']->id);
    $response = (new SaleItemController())->destroy(
        $item,
        app(DeleteSaleItemAction::class),
        new EnsureSaleItemInBranchAction()
    );

    expect($response->getTargetUrl())->toBe(saleItemsTenantRoute('sale-items.index'));
    expect($response->getSession()->get('status'))->toBe('Deleted.');
    $this->assertDatabaseMissing('sale_items', ['id' => $item->id], 'tenant');

    $fixture['sale']->refresh();
    expect((float) $fixture['sale']->sub_total)->toBe(0.0);
    expect((float) $fixture['sale']->grand_total)->toBe(0.0);
    expect((float) $fixture['sale']->balance_due)->toBe(0.0);

    $stock = InventoryStock::query()
        ->where('branch_id', $fixture['current']->id)
        ->where('product_id', $fixture['product']->id)
        ->firstOrFail();

    expect((float) $stock->qty_on_hand)->toBe(10.0);
});

it('does not adjust inventory for non tracked product sale items', function (): void {
    $fixture = authenticateSaleItemsUser();
    $fixture['product']->update(['track_stock' => false]);

    InventoryStock::query()->create([
        'product_id' => $fixture['product']->id,
        'branch_id' => $fixture['current']->id,
        'qty_on_hand' => 10,
        'qty_reserved' => 0,
        'avg_cost' => 0,
    ]);

    $response = $this->post(saleItemsTenantRoute('sale-items.store'), [
        'sale_id' => $fixture['sale']->id,
        'line_type' => SaleLineType::PRODUCT->value,
        'product_id' => $fixture['product']->id,
        'qty' => 2,
        'unit_price' => 100,
    ]);

    $response->assertRedirect(saleItemsTenantRoute('sale-items.index'));

    $stock = InventoryStock::query()
        ->where('branch_id', $fixture['current']->id)
        ->where('product_id', $fixture['product']->id)
        ->firstOrFail();

    expect((float) $stock->qty_on_hand)->toBe(10.0);
});

it('throws not found when showing sale item outside current branch', function (): void {
    $fixture = authenticateSaleItemsUser();

    $foreignSale = Sale::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['secondary']->id,
        'created_by' => $fixture['user']->id,
        'invoice_no' => 'SI-INV-3',
        'invoice_date' => now()->toDateString(),
        'status' => SaleStatus::POSTED->value,
        'invoice_type' => InvoiceType::PRODUCT->value,
    ]);

    $foreignItem = SaleItem::query()->withoutGlobalScopes()->create([
        'sale_id' => $foreignSale->id,
        'branch_id' => $fixture['secondary']->id,
        'line_type' => SaleLineType::PRODUCT->value,
        'product_id' => $fixture['product']->id,
        'qty' => 1,
        'unit_price' => 100,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'line_total' => 100,
    ]);

    $this->expectException(NotFoundHttpException::class);
    (new SaleItemController())->show($foreignItem, new EnsureSaleItemInBranchAction());
});

<?php

declare(strict_types=1);

use App\Enums\BranchStatus;
use App\Enums\InvoiceType;
use App\Enums\RecordStatus;
use App\Enums\SaleLineType;
use App\Enums\SaleStatus;
use App\Http\Controllers\Tenant\SaleItemController;
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
 * @return array{current: Branch, secondary: Branch, sale: Sale, product: Product, service: ServiceCatalog, user: User}
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
    expect($response->viewData('items')->total())->toBe(1);
});

it('clamps sale items pagination limits', function (): void {
    authenticateSaleItemsUser();

    $minResponse = $this->get(saleItemsTenantRoute('sale-items.index', ['per_page' => 1]));
    $maxResponse = $this->get(saleItemsTenantRoute('sale-items.index', ['per_page' => 999]));

    expect($minResponse->viewData('items')->perPage())->toBe(5);
    expect($maxResponse->viewData('items')->perPage())->toBe(100);
});

it('stores sale item and recalculates parent sale totals', function (): void {
    $fixture = authenticateSaleItemsUser();

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
    $response = (new SaleItemController())->destroy($item);

    expect($response->getTargetUrl())->toBe(saleItemsTenantRoute('sale-items.index'));
    expect($response->getSession()->get('status'))->toBe('Deleted.');
    $this->assertDatabaseMissing('sale_items', ['id' => $item->id], 'tenant');

    $fixture['sale']->refresh();
    expect((float) $fixture['sale']->sub_total)->toBe(0.0);
    expect((float) $fixture['sale']->grand_total)->toBe(0.0);
    expect((float) $fixture['sale']->balance_due)->toBe(0.0);
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
    (new SaleItemController())->show($foreignItem);
});

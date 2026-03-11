<?php

declare(strict_types=1);

use App\Actions\Tenant\SaleReturn\EnsureSaleReturnInBranchAction;
use App\Enums\BranchStatus;
use App\Enums\RecordStatus;
use App\Enums\SaleLineType;
use App\Enums\SaleReturnStatus;
use App\Enums\SaleStatus;
use App\Http\Controllers\Tenant\SaleReturnController;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
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

function saleReturnsTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

/**
 * @return array{current: Branch, secondary: Branch, customer: Customer, sale: Sale, saleItem: SaleItem, product: Product, tax: Tax, user: User}
 */
function authenticateSaleReturnsUser(): array
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
        'name' => 'SaleReturn User',
        'email' => 'sale.return.user+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => 'active',
    ]);

    $customer = Customer::query()->withoutGlobalScopes()->create([
        'branch_id' => $currentBranch->id,
        'code' => 'CUS-SR-1',
        'name' => 'Sales Return Customer',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $sale = Sale::query()->withoutGlobalScopes()->create([
        'branch_id' => $currentBranch->id,
        'customer_id' => $customer->id,
        'created_by' => $user->id,
        'invoice_no' => 'SR-INV-1',
        'invoice_date' => now()->toDateString(),
        'status' => SaleStatus::POSTED->value,
        'invoice_type' => 'product',
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
        'name' => 'Electrical',
        'slug' => 'electrical-sale-return',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $product = Product::query()->create([
        'category_id' => $category->id,
        'default_tax_id' => $tax->id,
        'sku' => 'SR-P-1',
        'name' => 'Alternator',
        'track_stock' => true,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $saleItem = SaleItem::query()->withoutGlobalScopes()->create([
        'sale_id' => $sale->id,
        'branch_id' => $currentBranch->id,
        'product_id' => $product->id,
        'line_type' => SaleLineType::PRODUCT->value,
        'qty' => 3,
        'unit_price' => 100,
        'tax_amount' => 0,
        'line_total' => 300,
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $currentBranch->id]);

    return [
        'current' => $currentBranch,
        'secondary' => $secondaryBranch,
        'customer' => $customer,
        'sale' => $sale,
        'saleItem' => $saleItem,
        'product' => $product,
        'tax' => $tax,
        'user' => $user,
    ];
}

it('stores sale return and increases stock quantity', function (): void {
    $fixture = authenticateSaleReturnsUser();

    InventoryStock::query()->create([
        'branch_id' => $fixture['current']->id,
        'product_id' => $fixture['product']->id,
        'qty_on_hand' => 5,
        'qty_reserved' => 0,
        'avg_cost' => 0,
    ]);

    $response = $this->post(saleReturnsTenantRoute('sale-returns.store'), [
        'customer_id' => $fixture['customer']->id,
        'sale_id' => $fixture['sale']->id,
        'return_no' => 'SR-RET-1',
        'return_date' => now()->toDateString(),
        'status' => SaleReturnStatus::POSTED->value,
        'items' => [[
            'sale_item_id' => $fixture['saleItem']->id,
            'product_id' => $fixture['product']->id,
            'tax_id' => $fixture['tax']->id,
            'qty' => 1,
            'unit_price' => 100,
            'tax_amount' => 10,
        ]],
    ]);

    $response->assertRedirect(saleReturnsTenantRoute('sale-returns.index'));

    $saleReturn = SaleReturn::query()->where('return_no', 'SR-RET-1')->firstOrFail();

    expect((float) $saleReturn->sub_total)->toBe(100.0);
    expect((float) $saleReturn->tax_total)->toBe(10.0);
    expect((float) $saleReturn->grand_total)->toBe(110.0);
    expect((float) InventoryStock::query()
        ->where('branch_id', $fixture['current']->id)
        ->where('product_id', $fixture['product']->id)
        ->value('qty_on_hand'))
        ->toBe(6.0);
});

it('fetches invoice product items with available return quantity', function (): void {
    $fixture = authenticateSaleReturnsUser();

    $existingReturn = SaleReturn::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'customer_id' => $fixture['customer']->id,
        'sale_id' => $fixture['sale']->id,
        'created_by' => $fixture['user']->id,
        'return_no' => 'SR-RET-EXISTING-FETCH',
        'return_date' => now()->toDateString(),
        'status' => SaleReturnStatus::POSTED->value,
    ]);

    $existingReturn->items()->create([
        'sale_item_id' => $fixture['saleItem']->id,
        'product_id' => $fixture['product']->id,
        'qty' => 1,
        'unit_price' => 100,
        'tax_amount' => 0,
        'line_total' => 100,
    ]);

    session()->put('tenant.current_branch_id', $fixture['current']->id);
    $response = (new SaleReturnController())->invoiceItems((string) $fixture['sale']->id);
    $payload = $response->getData(true);

    expect($payload['items'])->toHaveCount(1);
    expect($payload['items'][0]['sale_item_id'])->toBe($fixture['saleItem']->id);
    expect($payload['items'][0]['product_id'])->toBe($fixture['product']->id);
    expect((float) $payload['items'][0]['available_qty'])->toBe(2.0);
});

it('validates return quantity cannot exceed sold quantity', function (): void {
    $fixture = authenticateSaleReturnsUser();

    SaleReturn::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'customer_id' => $fixture['customer']->id,
        'sale_id' => $fixture['sale']->id,
        'created_by' => $fixture['user']->id,
        'return_no' => 'SR-RET-EXISTING',
        'return_date' => now()->toDateString(),
        'status' => SaleReturnStatus::POSTED->value,
    ])->items()->create([
        'sale_item_id' => $fixture['saleItem']->id,
        'product_id' => $fixture['product']->id,
        'qty' => 2,
        'unit_price' => 100,
        'tax_amount' => 0,
        'line_total' => 200,
    ]);

    $response = $this->from(saleReturnsTenantRoute('sale-returns.create'))
        ->post(saleReturnsTenantRoute('sale-returns.store'), [
            'customer_id' => $fixture['customer']->id,
            'sale_id' => $fixture['sale']->id,
            'return_no' => 'SR-RET-INVALID',
            'return_date' => now()->toDateString(),
            'status' => SaleReturnStatus::DRAFT->value,
            'items' => [[
                'sale_item_id' => $fixture['saleItem']->id,
                'product_id' => $fixture['product']->id,
                'qty' => 2,
                'unit_price' => 100,
                'tax_amount' => 0,
            ]],
        ]);

    $response->assertRedirect(saleReturnsTenantRoute('sale-returns.create'));
    $response->assertSessionHasErrors(['items.0.qty']);
});

it('throws not found when showing sale return outside current branch', function (): void {
    $fixture = authenticateSaleReturnsUser();

    $foreignReturn = SaleReturn::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['secondary']->id,
        'customer_id' => $fixture['customer']->id,
        'sale_id' => $fixture['sale']->id,
        'created_by' => $fixture['user']->id,
        'return_no' => 'SR-RET-404',
        'return_date' => now()->toDateString(),
        'status' => SaleReturnStatus::POSTED->value,
    ]);

    $this->expectException(NotFoundHttpException::class);
    (new SaleReturnController())->show($foreignReturn, new EnsureSaleReturnInBranchAction());
});

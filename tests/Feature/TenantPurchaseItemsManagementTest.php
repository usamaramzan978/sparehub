<?php

declare(strict_types=1);

use App\Actions\Tenant\PurchaseItem\DeletePurchaseItemAction;
use App\Actions\Tenant\PurchaseItem\EnsurePurchaseItemInBranchAction;
use App\Enums\BranchStatus;
use App\Enums\PurchaseStatus;
use App\Enums\RecordStatus;
use App\Http\Controllers\Tenant\PurchaseItemController;
use App\Models\Branch;
use App\Models\Category;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Tax;
use App\Models\User;
use App\Models\Vendor;
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

function purchaseItemsTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

/**
 * @return array{current: Branch, secondary: Branch, purchase: Purchase, product: Product, tax: Tax, user: User}
 */
function authenticatePurchaseItemsUser(): array
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
        'name' => 'PurchaseItem User',
        'email' => 'purchase.item.user+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => 'active',
    ]);

    $vendor = Vendor::query()->withoutGlobalScopes()->create([
        'branch_id' => $currentBranch->id,
        'code' => 'VEN-PI-1',
        'name' => 'Purchase Item Vendor',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $purchase = Purchase::query()->withoutGlobalScopes()->create([
        'branch_id' => $currentBranch->id,
        'vendor_id' => $vendor->id,
        'created_by' => $user->id,
        'purchase_no' => 'PI-PUR-1',
        'purchase_date' => now()->toDateString(),
        'status' => PurchaseStatus::POSTED->value,
        'shipping_total' => 10,
        'grand_total' => 10,
        'balance_due' => 10,
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
        'sku' => 'PI-P-1',
        'name' => 'Engine Oil',
        'track_stock' => true,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $currentBranch->id]);

    return [
        'current' => $currentBranch,
        'secondary' => $secondaryBranch,
        'purchase' => $purchase,
        'product' => $product,
        'tax' => $tax,
        'user' => $user,
    ];
}

it('shows purchase items index for current branch only', function (): void {
    $fixture = authenticatePurchaseItemsUser();

    PurchaseItem::query()->withoutGlobalScopes()->create([
        'purchase_id' => $fixture['purchase']->id,
        'branch_id' => $fixture['current']->id,
        'product_id' => $fixture['product']->id,
        'tax_id' => $fixture['tax']->id,
        'qty' => 1,
        'received_qty' => 1,
        'unit_cost' => 100,
        'discount_amount' => 0,
        'tax_amount' => 10,
        'line_total' => 110,
    ]);

    $otherVendor = Vendor::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['secondary']->id,
        'code' => 'VEN-PI-2',
        'name' => 'Secondary Vendor',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $otherPurchase = Purchase::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['secondary']->id,
        'vendor_id' => $otherVendor->id,
        'created_by' => $fixture['user']->id,
        'purchase_no' => 'PI-PUR-2',
        'purchase_date' => now()->toDateString(),
        'status' => PurchaseStatus::POSTED->value,
    ]);

    PurchaseItem::query()->withoutGlobalScopes()->create([
        'purchase_id' => $otherPurchase->id,
        'branch_id' => $fixture['secondary']->id,
        'product_id' => $fixture['product']->id,
        'qty' => 1,
        'unit_cost' => 50,
        'line_total' => 50,
    ]);

    $response = $this->get(purchaseItemsTenantRoute('purchase-items.index'));

    $response->assertSuccessful();
    $response->assertSee('data-ajax-table-search', false);
    $response->assertSee('id="purchase-items-search-form"', false);

    expect($response->viewData('items')->total())->toBe(1);
});

it('clamps purchase items pagination limits', function (): void {
    authenticatePurchaseItemsUser();

    $minResponse = $this->get(purchaseItemsTenantRoute('purchase-items.index', ['per_page' => 1]));
    $maxResponse = $this->get(purchaseItemsTenantRoute('purchase-items.index', ['per_page' => 999]));

    expect($minResponse->viewData('items')->perPage())->toBe(5);
    expect($maxResponse->viewData('items')->perPage())->toBe(100);
});

it('searches purchase items by purchase number and product', function (): void {
    $fixture = authenticatePurchaseItemsUser();

    PurchaseItem::query()->withoutGlobalScopes()->create([
        'purchase_id' => $fixture['purchase']->id,
        'branch_id' => $fixture['current']->id,
        'product_id' => $fixture['product']->id,
        'tax_id' => $fixture['tax']->id,
        'qty' => 1,
        'received_qty' => 1,
        'unit_cost' => 90,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'line_total' => 90,
    ]);

    $purchaseTwo = Purchase::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'vendor_id' => $fixture['purchase']->vendor_id,
        'created_by' => $fixture['user']->id,
        'purchase_no' => 'PI-PUR-SEARCH-2',
        'purchase_date' => now()->toDateString(),
        'status' => PurchaseStatus::POSTED->value,
    ]);

    PurchaseItem::query()->withoutGlobalScopes()->create([
        'purchase_id' => $purchaseTwo->id,
        'branch_id' => $fixture['current']->id,
        'product_id' => $fixture['product']->id,
        'tax_id' => $fixture['tax']->id,
        'qty' => 1,
        'received_qty' => 1,
        'unit_cost' => 120,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'line_total' => 120,
    ]);

    $byPurchase = $this->get(purchaseItemsTenantRoute('purchase-items.index', ['search' => 'SEARCH-2']));
    $byProduct = $this->get(purchaseItemsTenantRoute('purchase-items.index', ['search' => 'Engine Oil']));

    expect($byPurchase->viewData('items')->total())->toBe(1);
    expect($byProduct->viewData('items')->total())->toBe(2);
});

it('stores purchase item and recalculates parent purchase totals', function (): void {
    $fixture = authenticatePurchaseItemsUser();

    $response = $this->post(purchaseItemsTenantRoute('purchase-items.store'), [
        'purchase_id' => $fixture['purchase']->id,
        'product_id' => $fixture['product']->id,
        'tax_id' => $fixture['tax']->id,
        'qty' => 2,
        'received_qty' => 2,
        'unit_cost' => 100,
        'discount_amount' => 10,
        'tax_amount' => 20,
    ]);

    $response->assertRedirect(purchaseItemsTenantRoute('purchase-items.index'));

    $fixture['purchase']->refresh();
    expect((float) $fixture['purchase']->sub_total)->toBe(200.0);
    expect((float) $fixture['purchase']->discount_total)->toBe(10.0);
    expect((float) $fixture['purchase']->tax_total)->toBe(20.0);
    expect((float) $fixture['purchase']->grand_total)->toBe(220.0);
    expect((float) $fixture['purchase']->balance_due)->toBe(220.0);
    expect((float) InventoryStock::query()
        ->where('branch_id', $fixture['current']->id)
        ->where('product_id', $fixture['product']->id)
        ->value('qty_on_hand'))
        ->toBe(2.0);
});

it('validates required purchase and product for purchase item', function (): void {
    authenticatePurchaseItemsUser();

    $response = $this->from(purchaseItemsTenantRoute('purchase-items.create'))
        ->post(purchaseItemsTenantRoute('purchase-items.store'), [
            'qty' => 1,
            'unit_cost' => 100,
        ]);

    $response->assertRedirect(purchaseItemsTenantRoute('purchase-items.create'));
    $response->assertSessionHasErrors(['purchase_id', 'product_id']);
});

it('deletes purchase item and recalculates purchase totals', function (): void {
    $fixture = authenticatePurchaseItemsUser();

    $item = PurchaseItem::query()->withoutGlobalScopes()->create([
        'purchase_id' => $fixture['purchase']->id,
        'branch_id' => $fixture['current']->id,
        'product_id' => $fixture['product']->id,
        'tax_id' => $fixture['tax']->id,
        'qty' => 2,
        'received_qty' => 2,
        'unit_cost' => 100,
        'discount_amount' => 10,
        'tax_amount' => 20,
        'line_total' => 210,
    ]);

    $fixture['purchase']->update([
        'sub_total' => 200,
        'discount_total' => 10,
        'tax_total' => 20,
        'grand_total' => 220,
        'balance_due' => 220,
    ]);

    session()->put('tenant.current_branch_id', $fixture['current']->id);
    $response = (new PurchaseItemController())->destroy(
        $item,
        app(DeletePurchaseItemAction::class),
        new EnsurePurchaseItemInBranchAction()
    );

    expect($response->getTargetUrl())->toBe(purchaseItemsTenantRoute('purchase-items.index'));
    expect($response->getSession()->get('status'))->toBe('Deleted.');
    $this->assertDatabaseMissing('purchase_items', ['id' => $item->id], 'tenant');

    $fixture['purchase']->refresh();
    expect((float) $fixture['purchase']->sub_total)->toBe(0.0);
    expect((float) $fixture['purchase']->grand_total)->toBe(10.0);
    expect((float) $fixture['purchase']->balance_due)->toBe(10.0);
});

it('throws not found when showing purchase item outside current branch', function (): void {
    $fixture = authenticatePurchaseItemsUser();

    $otherVendor = Vendor::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['secondary']->id,
        'code' => 'VEN-PI-3',
        'name' => 'Alt Vendor',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $otherPurchase = Purchase::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['secondary']->id,
        'vendor_id' => $otherVendor->id,
        'created_by' => $fixture['user']->id,
        'purchase_no' => 'PI-PUR-3',
        'purchase_date' => now()->toDateString(),
        'status' => PurchaseStatus::POSTED->value,
    ]);

    $foreignItem = PurchaseItem::query()->withoutGlobalScopes()->create([
        'purchase_id' => $otherPurchase->id,
        'branch_id' => $fixture['secondary']->id,
        'product_id' => $fixture['product']->id,
        'qty' => 1,
        'unit_cost' => 100,
        'line_total' => 100,
    ]);

    $this->expectException(NotFoundHttpException::class);
    (new PurchaseItemController())->show($foreignItem, new EnsurePurchaseItemInBranchAction());
});

<?php

declare(strict_types=1);

use App\Enums\BranchStatus;
use App\Enums\PurchaseReturnStatus;
use App\Enums\PurchaseStatus;
use App\Enums\RecordStatus;
use App\Http\Controllers\Tenant\PurchaseReturnItemController;
use App\Models\Branch;
use App\Models\Category;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
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

function purchaseReturnItemsTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

/**
 * @return array{current: Branch, secondary: Branch, purchaseReturn: PurchaseReturn, purchaseItem: PurchaseItem, product: Product, tax: Tax, user: User}
 */
function authenticatePurchaseReturnItemsUser(): array
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
        'name' => 'PurchaseReturnItem User',
        'email' => 'purchase.return.item.user+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => 'active',
    ]);

    $vendor = Vendor::query()->withoutGlobalScopes()->create([
        'branch_id' => $currentBranch->id,
        'code' => 'VEN-PRI-1',
        'name' => 'PRI Vendor',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $purchase = Purchase::query()->withoutGlobalScopes()->create([
        'branch_id' => $currentBranch->id,
        'vendor_id' => $vendor->id,
        'created_by' => $user->id,
        'purchase_no' => 'PRI-PUR-1',
        'purchase_date' => now()->toDateString(),
        'status' => PurchaseStatus::POSTED->value,
    ]);

    $tax = Tax::query()->create([
        'code' => 'GST',
        'name' => 'GST',
        'rate' => 17,
        'is_inclusive' => false,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $category = Category::query()->create([
        'name' => 'Suspension',
        'slug' => 'suspension',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $product = Product::query()->create([
        'category_id' => $category->id,
        'default_tax_id' => $tax->id,
        'sku' => 'PRI-P-1',
        'name' => 'Shock Absorber',
        'track_stock' => true,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $purchaseItem = PurchaseItem::query()->withoutGlobalScopes()->create([
        'purchase_id' => $purchase->id,
        'branch_id' => $currentBranch->id,
        'product_id' => $product->id,
        'tax_id' => $tax->id,
        'qty' => 2,
        'received_qty' => 2,
        'unit_cost' => 120,
        'tax_amount' => 20,
        'line_total' => 260,
    ]);

    $purchaseReturn = PurchaseReturn::query()->withoutGlobalScopes()->create([
        'branch_id' => $currentBranch->id,
        'vendor_id' => $vendor->id,
        'purchase_id' => $purchase->id,
        'created_by' => $user->id,
        'return_no' => 'PRI-RET-1',
        'return_date' => now()->toDateString(),
        'status' => PurchaseReturnStatus::POSTED->value,
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $currentBranch->id]);

    return [
        'current' => $currentBranch,
        'secondary' => $secondaryBranch,
        'purchaseReturn' => $purchaseReturn,
        'purchaseItem' => $purchaseItem,
        'product' => $product,
        'tax' => $tax,
        'user' => $user,
    ];
}

it('shows purchase return items index for current branch only', function (): void {
    $fixture = authenticatePurchaseReturnItemsUser();

    PurchaseReturnItem::query()->create([
        'purchase_return_id' => $fixture['purchaseReturn']->id,
        'purchase_item_id' => $fixture['purchaseItem']->id,
        'product_id' => $fixture['product']->id,
        'tax_id' => $fixture['tax']->id,
        'qty' => 1,
        'unit_cost' => 100,
        'tax_amount' => 10,
        'line_total' => 110,
    ]);

    $otherVendor = Vendor::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['secondary']->id,
        'code' => 'VEN-PRI-2',
        'name' => 'Alt Vendor',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $otherPurchase = Purchase::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['secondary']->id,
        'vendor_id' => $otherVendor->id,
        'created_by' => $fixture['user']->id,
        'purchase_no' => 'PRI-PUR-2',
        'purchase_date' => now()->toDateString(),
        'status' => PurchaseStatus::POSTED->value,
    ]);

    $otherReturn = PurchaseReturn::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['secondary']->id,
        'vendor_id' => $otherVendor->id,
        'purchase_id' => $otherPurchase->id,
        'created_by' => $fixture['user']->id,
        'return_no' => 'PRI-RET-2',
        'return_date' => now()->toDateString(),
        'status' => PurchaseReturnStatus::POSTED->value,
    ]);

    PurchaseReturnItem::query()->create([
        'purchase_return_id' => $otherReturn->id,
        'product_id' => $fixture['product']->id,
        'qty' => 1,
        'unit_cost' => 50,
        'line_total' => 50,
    ]);

    $response = $this->get(purchaseReturnItemsTenantRoute('purchase-return-items.index'));

    $response->assertSuccessful();

    expect($response->viewData('items')->total())->toBe(1);
});

it('clamps purchase return items pagination limits', function (): void {
    authenticatePurchaseReturnItemsUser();

    $minResponse = $this->get(purchaseReturnItemsTenantRoute('purchase-return-items.index', ['per_page' => 1]));
    $maxResponse = $this->get(purchaseReturnItemsTenantRoute('purchase-return-items.index', ['per_page' => 999]));

    expect($minResponse->viewData('items')->perPage())->toBe(5);
    expect($maxResponse->viewData('items')->perPage())->toBe(100);
});

it('stores purchase return item and recalculates parent return totals', function (): void {
    $fixture = authenticatePurchaseReturnItemsUser();
    InventoryStock::query()->create([
        'branch_id' => $fixture['current']->id,
        'product_id' => $fixture['product']->id,
        'qty_on_hand' => 10,
        'qty_reserved' => 0,
        'avg_cost' => 0,
    ]);

    $response = $this->post(purchaseReturnItemsTenantRoute('purchase-return-items.store'), [
        'purchase_return_id' => $fixture['purchaseReturn']->id,
        'purchase_item_id' => $fixture['purchaseItem']->id,
        'product_id' => $fixture['product']->id,
        'tax_id' => $fixture['tax']->id,
        'qty' => 2,
        'unit_cost' => 100,
        'tax_amount' => 20,
    ]);

    $response->assertRedirect(purchaseReturnItemsTenantRoute('purchase-return-items.index'));

    $fixture['purchaseReturn']->refresh();
    expect((float) $fixture['purchaseReturn']->sub_total)->toBe(200.0);
    expect((float) $fixture['purchaseReturn']->tax_total)->toBe(20.0);
    expect((float) $fixture['purchaseReturn']->grand_total)->toBe(220.0);
    expect((float) InventoryStock::query()
        ->where('branch_id', $fixture['current']->id)
        ->where('product_id', $fixture['product']->id)
        ->value('qty_on_hand'))
        ->toBe(8.0);
});

it('validates required purchase return and product for return item', function (): void {
    authenticatePurchaseReturnItemsUser();

    $response = $this->from(purchaseReturnItemsTenantRoute('purchase-return-items.create'))
        ->post(purchaseReturnItemsTenantRoute('purchase-return-items.store'), [
            'qty' => 1,
            'unit_cost' => 100,
        ]);

    $response->assertRedirect(purchaseReturnItemsTenantRoute('purchase-return-items.create'));
    $response->assertSessionHasErrors(['purchase_return_id', 'product_id']);
});

it('deletes purchase return item and recalculates return totals', function (): void {
    $fixture = authenticatePurchaseReturnItemsUser();

    $item = PurchaseReturnItem::query()->create([
        'purchase_return_id' => $fixture['purchaseReturn']->id,
        'purchase_item_id' => $fixture['purchaseItem']->id,
        'product_id' => $fixture['product']->id,
        'tax_id' => $fixture['tax']->id,
        'qty' => 2,
        'unit_cost' => 100,
        'tax_amount' => 20,
        'line_total' => 220,
    ]);

    $fixture['purchaseReturn']->update([
        'sub_total' => 200,
        'tax_total' => 20,
        'grand_total' => 220,
    ]);

    session()->put('tenant.current_branch_id', $fixture['current']->id);
    $response = (new PurchaseReturnItemController())->destroy($item);

    expect($response->getTargetUrl())->toBe(purchaseReturnItemsTenantRoute('purchase-return-items.index'));
    expect($response->getSession()->get('status'))->toBe('Deleted.');
    $this->assertDatabaseMissing('purchase_return_items', ['id' => $item->id], 'tenant');

    $fixture['purchaseReturn']->refresh();
    expect((float) $fixture['purchaseReturn']->sub_total)->toBe(0.0);
    expect((float) $fixture['purchaseReturn']->tax_total)->toBe(0.0);
    expect((float) $fixture['purchaseReturn']->grand_total)->toBe(0.0);
});

it('throws not found when showing purchase return item outside current branch', function (): void {
    $fixture = authenticatePurchaseReturnItemsUser();

    $otherVendor = Vendor::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['secondary']->id,
        'code' => 'VEN-PRI-3',
        'name' => 'Alt Vendor 2',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $otherPurchase = Purchase::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['secondary']->id,
        'vendor_id' => $otherVendor->id,
        'created_by' => $fixture['user']->id,
        'purchase_no' => 'PRI-PUR-3',
        'purchase_date' => now()->toDateString(),
        'status' => PurchaseStatus::POSTED->value,
    ]);

    $otherReturn = PurchaseReturn::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['secondary']->id,
        'vendor_id' => $otherVendor->id,
        'purchase_id' => $otherPurchase->id,
        'created_by' => $fixture['user']->id,
        'return_no' => 'PRI-RET-3',
        'return_date' => now()->toDateString(),
        'status' => PurchaseReturnStatus::POSTED->value,
    ]);

    $foreignItem = PurchaseReturnItem::query()->create([
        'purchase_return_id' => $otherReturn->id,
        'product_id' => $fixture['product']->id,
        'qty' => 1,
        'unit_cost' => 100,
        'line_total' => 100,
    ]);

    $this->expectException(NotFoundHttpException::class);
    (new PurchaseReturnItemController())->show($foreignItem);
});

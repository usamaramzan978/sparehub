<?php

declare(strict_types=1);

use App\Actions\Tenant\PurchaseReturn\DeletePurchaseReturnAction;
use App\Actions\Tenant\PurchaseReturn\EnsurePurchaseReturnInBranchAction;
use App\Enums\BranchStatus;
use App\Enums\PurchaseReturnStatus;
use App\Enums\PurchaseStatus;
use App\Enums\RecordStatus;
use App\Http\Controllers\Tenant\PurchaseReturnController;
use App\Models\Branch;
use App\Models\Category;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
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

function purchaseReturnsTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

/**
 * @return array{current: Branch, secondary: Branch, purchase: Purchase, vendor: Vendor, product: Product, tax: Tax, user: User}
 */
function authenticatePurchaseReturnsUser(): array
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
        'name' => 'PurchaseReturn User',
        'email' => 'purchase.return.user+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => 'active',
    ]);

    $vendor = Vendor::query()->withoutGlobalScopes()->create([
        'branch_id' => $currentBranch->id,
        'code' => 'VEN-PR-1',
        'name' => 'Return Vendor',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $purchase = Purchase::query()->withoutGlobalScopes()->create([
        'branch_id' => $currentBranch->id,
        'vendor_id' => $vendor->id,
        'created_by' => $user->id,
        'purchase_no' => 'PR-PUR-1',
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
        'name' => 'Electrical',
        'slug' => 'electrical',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $product = Product::query()->create([
        'category_id' => $category->id,
        'default_tax_id' => $tax->id,
        'sku' => 'PR-P-1',
        'name' => 'Battery',
        'track_stock' => true,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $currentBranch->id]);

    return [
        'current' => $currentBranch,
        'secondary' => $secondaryBranch,
        'purchase' => $purchase,
        'vendor' => $vendor,
        'product' => $product,
        'tax' => $tax,
        'user' => $user,
    ];
}

it('shows purchase returns index for current branch only', function (): void {
    $fixture = authenticatePurchaseReturnsUser();

    PurchaseReturn::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'vendor_id' => $fixture['vendor']->id,
        'purchase_id' => $fixture['purchase']->id,
        'created_by' => $fixture['user']->id,
        'return_no' => 'RET-MAIN-1',
        'return_date' => now()->toDateString(),
        'status' => PurchaseReturnStatus::POSTED->value,
    ]);

    PurchaseReturn::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['secondary']->id,
        'vendor_id' => $fixture['vendor']->id,
        'created_by' => $fixture['user']->id,
        'return_no' => 'RET-ALT-1',
        'return_date' => now()->toDateString(),
        'status' => PurchaseReturnStatus::POSTED->value,
    ]);

    $response = $this->get(purchaseReturnsTenantRoute('purchase-returns.index'));

    $response->assertSuccessful();
    $response->assertSee('data-ajax-table-search', false);
    $response->assertSee('data-ajax-sort-link', false);
    $response->assertSee('id="purchase-returns-search-form"', false);

    expect($response->viewData('items')->total())->toBe(1);
});

it('sorts purchase returns by return number ascending and descending', function (): void {
    $fixture = authenticatePurchaseReturnsUser();

    PurchaseReturn::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'vendor_id' => $fixture['vendor']->id,
        'purchase_id' => $fixture['purchase']->id,
        'created_by' => $fixture['user']->id,
        'return_no' => 'RET-SORT-A',
        'return_date' => now()->toDateString(),
        'status' => PurchaseReturnStatus::POSTED->value,
    ]);

    PurchaseReturn::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'vendor_id' => $fixture['vendor']->id,
        'purchase_id' => $fixture['purchase']->id,
        'created_by' => $fixture['user']->id,
        'return_no' => 'RET-SORT-Z',
        'return_date' => now()->toDateString(),
        'status' => PurchaseReturnStatus::POSTED->value,
    ]);

    $ascending = $this->get(purchaseReturnsTenantRoute('purchase-returns.index', [
        'sort_by' => 'return_no',
        'sort_direction' => 'asc',
    ]));

    $descending = $this->get(purchaseReturnsTenantRoute('purchase-returns.index', [
        'sort_by' => 'return_no',
        'sort_direction' => 'desc',
    ]));

    $ascendingReturnNumbers = $ascending->viewData('items')->pluck('return_no')->values()->all();
    $descendingReturnNumbers = $descending->viewData('items')->pluck('return_no')->values()->all();

    expect(array_search('RET-SORT-A', $ascendingReturnNumbers, true))->toBeLessThan(array_search('RET-SORT-Z', $ascendingReturnNumbers, true));
    expect(array_search('RET-SORT-A', $descendingReturnNumbers, true))->toBeGreaterThan(array_search('RET-SORT-Z', $descendingReturnNumbers, true));
});

it('clamps purchase returns pagination limits', function (): void {
    authenticatePurchaseReturnsUser();

    $minResponse = $this->get(purchaseReturnsTenantRoute('purchase-returns.index', ['per_page' => 1]));
    $maxResponse = $this->get(purchaseReturnsTenantRoute('purchase-returns.index', ['per_page' => 999]));

    expect($minResponse->viewData('items')->perPage())->toBe(5);
    expect($maxResponse->viewData('items')->perPage())->toBe(100);
});

it('stores purchase return and syncs totals from items', function (): void {
    $fixture = authenticatePurchaseReturnsUser();
    InventoryStock::query()->create([
        'branch_id' => $fixture['current']->id,
        'product_id' => $fixture['product']->id,
        'qty_on_hand' => 10,
        'qty_reserved' => 0,
        'avg_cost' => 0,
    ]);

    $response = $this->post(purchaseReturnsTenantRoute('purchase-returns.store'), [
        'vendor_id' => $fixture['vendor']->id,
        'purchase_id' => $fixture['purchase']->id,
        'return_no' => 'RET-STORE-1',
        'return_date' => now()->toDateString(),
        'status' => PurchaseReturnStatus::POSTED->value,
        'items' => [
            [
                'product_id' => $fixture['product']->id,
                'tax_id' => $fixture['tax']->id,
                'qty' => 1,
                'unit_cost' => 100,
                'tax_amount' => 10,
            ],
            [
                'product_id' => $fixture['product']->id,
                'tax_id' => $fixture['tax']->id,
                'qty' => 2,
                'unit_cost' => 50,
                'tax_amount' => 5,
            ],
        ],
    ]);

    $response->assertRedirect(purchaseReturnsTenantRoute('purchase-returns.index'));

    $return = PurchaseReturn::query()->where('return_no', 'RET-STORE-1')->firstOrFail();

    expect((float) $return->sub_total)->toBe(200.0);
    expect((float) $return->tax_total)->toBe(15.0);
    expect((float) $return->grand_total)->toBe(215.0);
    expect($return->items()->count())->toBe(2);
    expect((float) InventoryStock::query()
        ->where('branch_id', $fixture['current']->id)
        ->where('product_id', $fixture['product']->id)
        ->value('qty_on_hand'))
        ->toBe(7.0);
});

it('validates required vendor and items when storing purchase return', function (): void {
    authenticatePurchaseReturnsUser();

    $response = $this->from(purchaseReturnsTenantRoute('purchase-returns.create'))
        ->post(purchaseReturnsTenantRoute('purchase-returns.store'), [
            'return_no' => 'RET-INVALID-1',
            'return_date' => now()->toDateString(),
            'status' => PurchaseReturnStatus::DRAFT->value,
        ]);

    $response->assertRedirect(purchaseReturnsTenantRoute('purchase-returns.create'));
    $response->assertSessionHasErrors(['vendor_id', 'items']);
});

it('deletes purchase return', function (): void {
    $fixture = authenticatePurchaseReturnsUser();

    $purchaseReturn = PurchaseReturn::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'vendor_id' => $fixture['vendor']->id,
        'purchase_id' => $fixture['purchase']->id,
        'created_by' => $fixture['user']->id,
        'return_no' => 'RET-DEL-1',
        'return_date' => now()->toDateString(),
        'status' => PurchaseReturnStatus::DRAFT->value,
    ]);

    session()->put('tenant.current_branch_id', $fixture['current']->id);
    $response = (new PurchaseReturnController())->destroy(
        $purchaseReturn,
        app(DeletePurchaseReturnAction::class),
        new EnsurePurchaseReturnInBranchAction()
    );

    expect($response->getTargetUrl())->toBe(purchaseReturnsTenantRoute('purchase-returns.index'));
    expect($response->getSession()->get('status'))->toBe('Deleted.');
    $this->assertSoftDeleted('purchase_returns', ['id' => $purchaseReturn->id], 'tenant');
});

it('throws not found when showing purchase return outside current branch', function (): void {
    $fixture = authenticatePurchaseReturnsUser();

    $foreignReturn = PurchaseReturn::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['secondary']->id,
        'vendor_id' => $fixture['vendor']->id,
        'created_by' => $fixture['user']->id,
        'return_no' => 'RET-ALT-404',
        'return_date' => now()->toDateString(),
        'status' => PurchaseReturnStatus::POSTED->value,
    ]);

    $this->expectException(NotFoundHttpException::class);
    (new PurchaseReturnController())->show($foreignReturn, new EnsurePurchaseReturnInBranchAction());
});

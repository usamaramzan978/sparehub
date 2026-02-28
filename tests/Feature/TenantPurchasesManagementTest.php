<?php

declare(strict_types=1);

use App\Actions\Tenant\Purchase\DeletePurchaseAction;
use App\Actions\Tenant\Purchase\EnsurePurchaseInBranchAction;
use App\Actions\Tenant\Purchase\UpdatePurchaseAction;
use App\Enums\BranchStatus;
use App\Enums\PurchaseStatus;
use App\Enums\RecordStatus;
use App\Http\Controllers\Tenant\PurchaseController;
use App\Models\Branch;
use App\Models\Category;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Purchase;
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

function purchasesTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

/**
 * @return array{current: Branch, secondary: Branch, user: User, vendor: Vendor, product: Product, tax: Tax}
 */
function authenticatePurchasesUser(): array
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
        'name' => 'Purchase User',
        'email' => 'purchase.user+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => 'active',
    ]);

    $vendor = Vendor::query()->withoutGlobalScopes()->create([
        'branch_id' => $currentBranch->id,
        'code' => 'VEN-P-1',
        'name' => 'Purchase Vendor',
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
        'name' => 'Parts',
        'slug' => 'parts',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $product = Product::query()->create([
        'category_id' => $category->id,
        'default_tax_id' => $tax->id,
        'sku' => 'PUR-P-1',
        'name' => 'Brake Pad',
        'track_stock' => true,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $currentBranch->id]);

    return [
        'current' => $currentBranch,
        'secondary' => $secondaryBranch,
        'user' => $user,
        'vendor' => $vendor,
        'product' => $product,
        'tax' => $tax,
    ];
}

it('shows purchases index for current branch only', function (): void {
    $fixture = authenticatePurchasesUser();

    Purchase::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'vendor_id' => $fixture['vendor']->id,
        'created_by' => $fixture['user']->id,
        'purchase_no' => 'PUR-MAIN-1',
        'purchase_date' => now()->toDateString(),
        'status' => PurchaseStatus::POSTED->value,
    ]);

    Purchase::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['secondary']->id,
        'vendor_id' => $fixture['vendor']->id,
        'created_by' => $fixture['user']->id,
        'purchase_no' => 'PUR-ALT-1',
        'purchase_date' => now()->toDateString(),
        'status' => PurchaseStatus::POSTED->value,
    ]);

    $response = $this->get(purchasesTenantRoute('purchases.index'));

    $response->assertSuccessful();
    $response->assertSee('data-ajax-table-search', false);
    $response->assertSee('data-ajax-sort-link', false);
    $response->assertSee('id="purchases-search-form"', false);

    expect($response->viewData('items')->total())->toBe(1);
});

it('sorts purchases by purchase number ascending and descending', function (): void {
    $fixture = authenticatePurchasesUser();

    Purchase::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'vendor_id' => $fixture['vendor']->id,
        'created_by' => $fixture['user']->id,
        'purchase_no' => 'PUR-SORT-A',
        'purchase_date' => now()->toDateString(),
        'status' => PurchaseStatus::POSTED->value,
    ]);

    Purchase::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'vendor_id' => $fixture['vendor']->id,
        'created_by' => $fixture['user']->id,
        'purchase_no' => 'PUR-SORT-Z',
        'purchase_date' => now()->toDateString(),
        'status' => PurchaseStatus::POSTED->value,
    ]);

    $ascending = $this->get(purchasesTenantRoute('purchases.index', [
        'sort_by' => 'purchase_no',
        'sort_direction' => 'asc',
    ]));

    $descending = $this->get(purchasesTenantRoute('purchases.index', [
        'sort_by' => 'purchase_no',
        'sort_direction' => 'desc',
    ]));

    $ascendingPurchaseNumbers = $ascending->viewData('items')->pluck('purchase_no')->values()->all();
    $descendingPurchaseNumbers = $descending->viewData('items')->pluck('purchase_no')->values()->all();

    expect(array_search('PUR-SORT-A', $ascendingPurchaseNumbers, true))->toBeLessThan(array_search('PUR-SORT-Z', $ascendingPurchaseNumbers, true));
    expect(array_search('PUR-SORT-A', $descendingPurchaseNumbers, true))->toBeGreaterThan(array_search('PUR-SORT-Z', $descendingPurchaseNumbers, true));
});

it('clamps purchases pagination limits', function (): void {
    authenticatePurchasesUser();

    $minResponse = $this->get(purchasesTenantRoute('purchases.index', ['per_page' => 1]));
    $maxResponse = $this->get(purchasesTenantRoute('purchases.index', ['per_page' => 999]));

    expect($minResponse->viewData('items')->perPage())->toBe(5);
    expect($maxResponse->viewData('items')->perPage())->toBe(100);
});

it('stores purchase and syncs totals from items', function (): void {
    $fixture = authenticatePurchasesUser();

    $response = $this->post(purchasesTenantRoute('purchases.store'), [
        'vendor_id' => $fixture['vendor']->id,
        'purchase_no' => 'PUR-STORE-1',
        'purchase_date' => now()->toDateString(),
        'status' => PurchaseStatus::POSTED->value,
        'items' => [
            [
                'product_id' => $fixture['product']->id,
                'qty' => 2,
                'cost' => 100,
                'mrp' => 150,
                'retail_price' => 130,
                'wholesale_price' => 120,
            ],
            [
                'product_id' => $fixture['product']->id,
                'qty' => 1,
                'cost' => 200,
                'mrp' => 260,
                'retail_price' => 230,
                'wholesale_price' => 220,
            ],
        ],
    ]);

    $response->assertRedirect(purchasesTenantRoute('purchases.index'));

    $purchase = Purchase::query()->where('purchase_no', 'PUR-STORE-1')->firstOrFail();

    expect((float) $purchase->grand_total)->toBe(400.0);
    expect($purchase->items()->count())->toBe(2);
    expect((string) $purchase->items()->firstOrFail()->remarks)->toBe('Stock qty updated and product prices synced from this purchase item.');
    expect((float) InventoryStock::query()
        ->where('branch_id', $fixture['current']->id)
        ->where('product_id', $fixture['product']->id)
        ->value('qty_on_hand'))
        ->toBe(3.0);

    $latestPrice = ProductPrice::query()
        ->where('branch_id', $fixture['current']->id)
        ->where('product_id', $fixture['product']->id)
        ->firstOrFail();

    expect((float) $latestPrice->cost)->toBe(200.0);
    expect((float) $latestPrice->mrp)->toBe(260.0);
    expect((float) $latestPrice->retail_price)->toBe(230.0);
    expect((float) $latestPrice->wholesale_price)->toBe(220.0);
});

it('auto generates purchase number when purchase no is empty on store', function (): void {
    $fixture = authenticatePurchasesUser();

    $response = $this->post(purchasesTenantRoute('purchases.store'), [
        'vendor_id' => $fixture['vendor']->id,
        'purchase_no' => '',
        'purchase_date' => now()->toDateString(),
        'status' => PurchaseStatus::POSTED->value,
        'items' => [
            [
                'product_id' => $fixture['product']->id,
                'qty' => 1,
                'cost' => 100,
                'mrp' => 120,
                'retail_price' => 110,
                'wholesale_price' => 105,
            ],
        ],
    ]);

    $response->assertRedirect(purchasesTenantRoute('purchases.index'));

    $purchase = Purchase::query()->latest('created_at')->firstOrFail();
    expect($purchase->purchase_no)->toMatch('/^PUR-\d{8}-\d{4}$/');
});

it('keeps purchase number unchanged on update even when purchase no is empty', function (): void {
    $fixture = authenticatePurchasesUser();

    $purchase = Purchase::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'vendor_id' => $fixture['vendor']->id,
        'created_by' => $fixture['user']->id,
        'purchase_no' => 'PUR-UPD-OLD-1',
        'purchase_date' => now()->toDateString(),
        'status' => PurchaseStatus::DRAFT->value,
    ]);

    app(UpdatePurchaseAction::class)->handle($purchase, [
        'vendor_id' => $fixture['vendor']->id,
        'purchase_no' => '',
        'purchase_date' => now()->toDateString(),
        'status' => PurchaseStatus::POSTED->value,
        'items' => [
            [
                'product_id' => $fixture['product']->id,
                'qty' => 1,
                'cost' => 100,
                'mrp' => 120,
                'retail_price' => 110,
                'wholesale_price' => 105,
            ],
        ],
    ], $fixture['current']->id);

    $purchase->refresh();
    expect($purchase->purchase_no)->toBe('PUR-UPD-OLD-1');
});

it('does not adjust stock for products with tracking disabled', function (): void {
    $fixture = authenticatePurchasesUser();

    $untrackedProduct = Product::query()->create([
        'category_id' => $fixture['product']->category_id,
        'default_tax_id' => $fixture['tax']->id,
        'sku' => 'PUR-P-2',
        'name' => 'Service Charge',
        'track_stock' => false,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $response = $this->post(purchasesTenantRoute('purchases.store'), [
        'vendor_id' => $fixture['vendor']->id,
        'purchase_no' => 'PUR-STORE-2',
        'purchase_date' => now()->toDateString(),
        'status' => PurchaseStatus::POSTED->value,
        'items' => [
            [
                'product_id' => $untrackedProduct->id,
                'qty' => 2,
                'cost' => 100,
                'mrp' => 130,
                'retail_price' => 120,
                'wholesale_price' => 110,
            ],
        ],
    ]);

    $response->assertRedirect(purchasesTenantRoute('purchases.index'));

    expect(InventoryStock::query()
        ->where('branch_id', $fixture['current']->id)
        ->where('product_id', $untrackedProduct->id)
        ->exists())
        ->toBeFalse();
});

it('validates required vendor and items when storing purchase', function (): void {
    authenticatePurchasesUser();

    $response = $this->from(purchasesTenantRoute('purchases.create'))
        ->post(purchasesTenantRoute('purchases.store'), [
            'purchase_no' => 'PUR-INVALID-1',
            'purchase_date' => now()->toDateString(),
            'status' => PurchaseStatus::DRAFT->value,
        ]);

    $response->assertRedirect(purchasesTenantRoute('purchases.create'));
    $response->assertSessionHasErrors(['vendor_id', 'items']);
});

it('deletes purchase', function (): void {
    $fixture = authenticatePurchasesUser();

    $purchase = Purchase::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'vendor_id' => $fixture['vendor']->id,
        'created_by' => $fixture['user']->id,
        'purchase_no' => 'PUR-DEL-1',
        'purchase_date' => now()->toDateString(),
        'status' => PurchaseStatus::DRAFT->value,
    ]);

    session()->put('tenant.current_branch_id', $fixture['current']->id);
    $response = (new PurchaseController())->destroy(
        $purchase,
        app(DeletePurchaseAction::class),
        new EnsurePurchaseInBranchAction()
    );

    expect($response->getTargetUrl())->toBe(purchasesTenantRoute('purchases.index'));
    expect($response->getSession()->get('status'))->toBe('Deleted.');
    $this->assertSoftDeleted('purchases', ['id' => $purchase->id], 'tenant');
});

it('throws not found when showing purchase outside current branch', function (): void {
    $fixture = authenticatePurchasesUser();

    $foreignPurchase = Purchase::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['secondary']->id,
        'vendor_id' => $fixture['vendor']->id,
        'created_by' => $fixture['user']->id,
        'purchase_no' => 'PUR-ALT-404',
        'purchase_date' => now()->toDateString(),
        'status' => PurchaseStatus::POSTED->value,
    ]);

    $this->expectException(NotFoundHttpException::class);
    (new PurchaseController())->show($foreignPurchase, new EnsurePurchaseInBranchAction());
});

<?php

declare(strict_types=1);

use App\Enums\BranchStatus;
use App\Enums\PurchaseStatus;
use App\Enums\RecordStatus;
use App\Enums\UserStatus;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\User;
use App\Models\Vendor;
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

function purchasesTreeTenantRoute(array $parameters = []): string
{
    return route('tenant.purchases-tree.index', ['tenant' => 'test-tenant-id', ...$parameters]);
}

function authenticatePurchasesTreeModuleUser(): array
{
    $branch = Branch::query()->create([
        'code' => 'MAIN',
        'name' => 'Main Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $user = User::query()->create([
        'branch_id' => $branch->id,
        'name' => 'PurchasesTree User',
        'email' => 'purchases.tree.user+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    $vendor = Vendor::query()->withoutGlobalScopes()->create([
        'branch_id' => $branch->id,
        'code' => 'PT-V-1',
        'name' => 'Tree Vendor',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $category = Category::query()->create([
        'name' => 'Electrical',
        'slug' => 'electrical',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $product = Product::query()->create([
        'category_id' => $category->id,
        'sku' => 'PT-P-1',
        'name' => 'Tree Purchase Product',
        'track_stock' => true,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $purchase = Purchase::query()->withoutGlobalScopes()->create([
        'branch_id' => $branch->id,
        'vendor_id' => $vendor->id,
        'created_by' => $user->id,
        'purchase_no' => 'PT-PUR-1',
        'purchase_date' => now()->toDateString(),
        'status' => PurchaseStatus::POSTED->value,
        'grand_total' => 400,
        'paid_total' => 150,
        'balance_due' => 250,
    ]);

    PurchaseItem::query()->withoutGlobalScopes()->create([
        'purchase_id' => $purchase->id,
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'qty' => 2,
        'received_qty' => 2,
        'unit_cost' => 200,
        'line_total' => 400,
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $branch->id]);

    return ['vendor' => $vendor];
}

it('shows purchases tree grouped data and summary', function (): void {
    authenticatePurchasesTreeModuleUser();

    $response = $this->get(purchasesTreeTenantRoute());

    $response->assertSuccessful();

    $summary = $response->viewData('summary');
    expect($summary['vendors_count'])->toBe(1);
    expect($summary['purchases_count'])->toBe(1);
    expect($summary['grand_total'])->toBe(400.0);
});

it('filters purchases tree by vendor', function (): void {
    $fixture = authenticatePurchasesTreeModuleUser();

    $response = $this->get(purchasesTreeTenantRoute([
        'vendor_id' => $fixture['vendor']->id,
    ]));

    $response->assertSuccessful();

    expect($response->viewData('summary')['purchases_count'])->toBe(1);
});

<?php

declare(strict_types=1);

use App\Enums\BranchStatus;
use App\Enums\RecordStatus;
use App\Enums\UserStatus;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\TenantSetting;
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

function contextTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

/**
 * @return array{primary: Branch, secondary: Branch, user: User}
 */
function createContextFixture(): array
{
    $primary = Branch::query()->create([
        'code' => 'MAIN',
        'name' => 'Primary Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $secondary = Branch::query()->create([
        'code' => 'ALT',
        'name' => 'Secondary Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    TenantSetting::query()->withoutGlobalScopes()->create([
        'branch_id' => $primary->id,
        'timezone' => 'Asia/Karachi',
    ]);

    TenantSetting::query()->withoutGlobalScopes()->create([
        'branch_id' => $secondary->id,
        'timezone' => 'UTC',
    ]);

    $user = User::query()->create([
        'branch_id' => $primary->id,
        'name' => 'Context User',
        'email' => 'context.user+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    return ['primary' => $primary, 'secondary' => $secondary, 'user' => $user];
}

it('applies fallback branch and tenant timezone on first authenticated request', function (): void {
    $fixture = createContextFixture();

    $this->actingAs($fixture['user'], 'user');

    $response = $this->get(contextTenantRoute('dashboard'));

    $response->assertSuccessful();

    expect(session('tenant.current_branch_id'))->toBe($fixture['primary']->id);
    expect(config('app.timezone'))->toBe('Asia/Karachi');
});

it('stores pos sale strictly in current session branch context', function (): void {
    $fixture = createContextFixture();

    $category = Category::query()->create([
        'name' => 'Engine',
        'slug' => 'engine-context',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $product = Product::query()->create([
        'category_id' => $category->id,
        'sku' => 'CTX-PRD-1',
        'name' => 'Context Product',
        'track_stock' => true,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $this->actingAs($fixture['user'], 'user');
    $this->withSession(['tenant.current_branch_id' => $fixture['primary']->id]);

    $response = $this->post(contextTenantRoute('pos.store'), [
        'status' => 'posted',
        'discount_type' => 'amount',
        'discount_value' => 0,
        'payment_mode' => 'cash',
        'cash_received' => 500,
        'items' => [[
            'type' => 'product',
            'ref_id' => $product->id,
            'qty' => 1,
            'price' => 100,
            'tax_rate' => 0,
            'tax_inclusive' => false,
        ]],
    ]);

    $response->assertRedirect(contextTenantRoute('pos.index'));

    $sale = Sale::query()->latest('created_at')->firstOrFail();
    expect($sale->branch_id)->toBe($fixture['primary']->id);
});

it('scopes reports by branch context and applies that branch timezone', function (): void {
    $fixture = createContextFixture();

    $customer = Customer::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['primary']->id,
        'code' => 'CUST-CTX',
        'name' => 'Context Customer',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $vendor = Vendor::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['primary']->id,
        'code' => 'VEND-CTX',
        'name' => 'Context Vendor',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    Sale::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['primary']->id,
        'created_by' => $fixture['user']->id,
        'customer_id' => $customer->id,
        'invoice_no' => 'CTX-SALE-1',
        'invoice_date' => now()->toDateString(),
        'status' => 'posted',
        'invoice_type' => 'product',
        'grand_total' => 300,
        'paid_total' => 300,
        'balance_due' => 0,
    ]);

    Sale::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['secondary']->id,
        'created_by' => $fixture['user']->id,
        'invoice_no' => 'CTX-SALE-2',
        'invoice_date' => now()->toDateString(),
        'status' => 'posted',
        'invoice_type' => 'product',
        'grand_total' => 700,
        'paid_total' => 700,
        'balance_due' => 0,
    ]);

    Purchase::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['primary']->id,
        'vendor_id' => $vendor->id,
        'created_by' => $fixture['user']->id,
        'purchase_no' => 'CTX-PUR-1',
        'purchase_date' => now()->toDateString(),
        'status' => 'posted',
        'grand_total' => 100,
    ]);

    $this->actingAs($fixture['user'], 'user');
    $this->withSession(['tenant.current_branch_id' => $fixture['primary']->id]);

    $response = $this->get(contextTenantRoute('reports.index'));

    $response->assertSuccessful();

    $summary = $response->viewData('summary');

    expect((float) $summary['sales_total'])->toBe(300.0);
    expect((float) $summary['purchases_total'])->toBe(100.0);
    expect(config('app.timezone'))->toBe('Asia/Karachi');
});

<?php

declare(strict_types=1);

use App\Actions\Tenant\Vendor\DeleteVendorAction;
use App\Actions\Tenant\Vendor\EnsureVendorInBranchAction;
use App\Enums\BranchStatus;
use App\Enums\RecordStatus;
use App\Http\Controllers\Tenant\VendorController;
use App\Models\Branch;
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

function vendorsTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

/**
 * @return array{current: Branch, secondary: Branch}
 */
function authenticateVendorUser(): array
{
    $currentBranch = Branch::query()->create([
        'code' => 'MAIN',
        'name' => 'Main Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $secondaryBranch = Branch::query()->create([
        'code' => 'ALT',
        'name' => 'Alternate Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $user = User::query()->create([
        'branch_id' => $currentBranch->id,
        'name' => 'Vendor User',
        'email' => 'vendor.user+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => 'active',
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $currentBranch->id]);

    return [
        'current' => $currentBranch,
        'secondary' => $secondaryBranch,
    ];
}

it('shows vendors index scoped to current branch', function (): void {
    $branches = authenticateVendorUser();

    Vendor::query()->withoutGlobalScopes()->create([
        'branch_id' => $branches['current']->id,
        'code' => 'VND-001',
        'name' => 'Main Vendor',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    Vendor::query()->withoutGlobalScopes()->create([
        'branch_id' => $branches['secondary']->id,
        'code' => 'VND-002',
        'name' => 'Other Vendor',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $response = $this->get(vendorsTenantRoute('vendors.index'));

    $response->assertSuccessful();
    $response->assertSee('Main Vendor');
    $response->assertDontSee('Other Vendor');
});

it('filters vendors by search', function (): void {
    $branches = authenticateVendorUser();

    Vendor::query()->withoutGlobalScopes()->create([
        'branch_id' => $branches['current']->id,
        'code' => 'VND-A',
        'name' => 'Alpha Vendor',
        'phone' => '1111111',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    Vendor::query()->withoutGlobalScopes()->create([
        'branch_id' => $branches['current']->id,
        'code' => 'VND-B',
        'name' => 'Beta Vendor',
        'phone' => '2222222',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $response = $this->get(vendorsTenantRoute('vendors.index', ['search' => 'Alpha']));

    $response->assertSuccessful();
    $response->assertSee('Alpha Vendor');
    $response->assertDontSee('Beta Vendor');
});

it('shows create vendor page', function (): void {
    authenticateVendorUser();

    $response = $this->get(vendorsTenantRoute('vendors.create'));

    $response->assertSuccessful();
    $response->assertSee('Create Vendor');
});

it('stores vendor for current branch', function (): void {
    $branches = authenticateVendorUser();

    $response = $this->post(vendorsTenantRoute('vendors.store'), [
        'code' => 'VND-NEW',
        'name' => 'New Vendor',
        'phone' => '03001234567',
        'email' => 'vendor.new@example.test',
        'opening_balance' => 1200,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $response->assertRedirect(vendorsTenantRoute('vendors.index'));
    $response->assertSessionHas('status', 'Created.');

    $this->assertDatabaseHas('vendors', [
        'branch_id' => $branches['current']->id,
        'code' => 'VND-NEW',
        'name' => 'New Vendor',
        'status' => RecordStatus::ACTIVE->value,
    ], 'tenant');
});

it('validates required vendor fields', function (string $field): void {
    authenticateVendorUser();

    $payload = [
        'code' => 'VND-REQ',
        'name' => 'Required Vendor',
        'status' => RecordStatus::ACTIVE->value,
    ];

    unset($payload[$field]);

    $response = $this->from(vendorsTenantRoute('vendors.create'))
        ->post(vendorsTenantRoute('vendors.store'), $payload);

    $response->assertRedirect(vendorsTenantRoute('vendors.create'));
    $response->assertSessionHasErrors([$field]);
})->with([
    'code' => 'code',
    'name' => 'name',
    'status' => 'status',
]);

it('validates vendor code uniqueness in current branch', function (): void {
    $branches = authenticateVendorUser();

    Vendor::query()->withoutGlobalScopes()->create([
        'branch_id' => $branches['current']->id,
        'code' => 'VND-DUP',
        'name' => 'Existing Vendor',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $response = $this->from(vendorsTenantRoute('vendors.create'))
        ->post(vendorsTenantRoute('vendors.store'), [
            'code' => 'VND-DUP',
            'name' => 'Duplicate Vendor',
            'status' => RecordStatus::ACTIVE->value,
        ]);

    $response->assertRedirect(vendorsTenantRoute('vendors.create'));
    $response->assertSessionHasErrors(['code' => 'This vendor code already exists for the selected branch.']);
});

it('allows same vendor code in different branch', function (): void {
    $branches = authenticateVendorUser();

    Vendor::query()->withoutGlobalScopes()->create([
        'branch_id' => $branches['secondary']->id,
        'code' => 'VND-SHARED',
        'name' => 'Other Branch Vendor',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $response = $this->post(vendorsTenantRoute('vendors.store'), [
        'code' => 'VND-SHARED',
        'name' => 'Current Branch Vendor',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $response->assertRedirect(vendorsTenantRoute('vendors.index'));

    $count = Vendor::query()->withoutGlobalScopes()->where('code', 'VND-SHARED')->count();
    expect($count)->toBe(2);
});

it('validates vendor email format', function (): void {
    authenticateVendorUser();

    $response = $this->from(vendorsTenantRoute('vendors.create'))
        ->post(vendorsTenantRoute('vendors.store'), [
            'code' => 'VND-EMAIL',
            'name' => 'Email Vendor',
            'email' => 'not-an-email',
            'status' => RecordStatus::ACTIVE->value,
        ]);

    $response->assertRedirect(vendorsTenantRoute('vendors.create'));
    $response->assertSessionHasErrors(['email']);
});

it('clamps vendors pagination limits', function (): void {
    authenticateVendorUser();

    $minResponse = $this->get(vendorsTenantRoute('vendors.index', ['per_page' => 1]));
    $maxResponse = $this->get(vendorsTenantRoute('vendors.index', ['per_page' => 999]));

    expect($minResponse->viewData('items')->perPage())->toBe(5);
    expect($maxResponse->viewData('items')->perPage())->toBe(100);
});

it('shows vendor details in current branch', function (): void {
    $branches = authenticateVendorUser();

    $vendor = Vendor::query()->withoutGlobalScopes()->create([
        'branch_id' => $branches['current']->id,
        'code' => 'VND-SHOW',
        'name' => 'Show Vendor',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    session()->put('tenant.current_branch_id', $branches['current']->id);
    $response = (new VendorController())->show($vendor, new EnsureVendorInBranchAction());

    expect($response->name())->toBe('tenants.vendors.show');
    expect($response->getData()['vendor']->id)->toBe($vendor->id);
});

it('shows edit vendor page for current branch vendor', function (): void {
    $branches = authenticateVendorUser();

    $vendor = Vendor::query()->withoutGlobalScopes()->create([
        'branch_id' => $branches['current']->id,
        'code' => 'VND-EDIT',
        'name' => 'Edit Vendor',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    session()->put('tenant.current_branch_id', $branches['current']->id);
    $response = (new VendorController())->edit($vendor, new EnsureVendorInBranchAction());

    expect($response->name())->toBe('tenants.vendors.edit');
    expect($response->getData()['vendor']->id)->toBe($vendor->id);
});

it('validates vendor status enum values', function (): void {
    authenticateVendorUser();

    $response = $this->from(vendorsTenantRoute('vendors.create'))
        ->post(vendorsTenantRoute('vendors.store'), [
            'code' => 'VND-BAD-STATUS',
            'name' => 'Bad Status Vendor',
            'status' => 'not-valid',
        ]);

    $response->assertRedirect(vendorsTenantRoute('vendors.create'));
    $response->assertSessionHasErrors(['status']);
});

it('deletes vendor', function (): void {
    $branches = authenticateVendorUser();

    $vendor = Vendor::query()->withoutGlobalScopes()->create([
        'branch_id' => $branches['current']->id,
        'code' => 'VND-DEL',
        'name' => 'Delete Vendor',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    session()->put('tenant.current_branch_id', $branches['current']->id);
    $response = (new VendorController())->destroy(
        $vendor,
        new DeleteVendorAction(),
        new EnsureVendorInBranchAction()
    );

    expect($response->getTargetUrl())->toBe(vendorsTenantRoute('vendors.index'));
    expect($response->getSession()->get('status'))->toBe('Deleted.');
    $this->assertSoftDeleted('vendors', ['id' => $vendor->id], 'tenant');
});

it('throws not found when showing vendor outside current branch', function (): void {
    $branches = authenticateVendorUser();

    $foreignVendor = Vendor::query()->withoutGlobalScopes()->create([
        'branch_id' => $branches['secondary']->id,
        'code' => 'VND-ALT-404',
        'name' => 'Foreign Vendor',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $this->expectException(NotFoundHttpException::class);
    (new VendorController())->show($foreignVendor, new EnsureVendorInBranchAction());
});

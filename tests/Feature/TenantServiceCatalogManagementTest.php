<?php

declare(strict_types=1);

use App\Enums\BranchStatus;
use App\Enums\RecordStatus;
use App\Http\Controllers\Tenant\ServiceCatalogController;
use App\Models\Branch;
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

function serviceCatalogTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

/**
 * @return array{current: Branch, secondary: Branch}
 */
function authenticateServiceCatalogUser(): array
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
        'name' => 'Service User',
        'email' => 'service.user+'.uniqid('', true).'@example.test',
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

it('shows service catalog index for current branch only', function (): void {
    $branches = authenticateServiceCatalogUser();

    ServiceCatalog::query()->withoutGlobalScopes()->create([
        'branch_id' => $branches['current']->id,
        'code' => 'SRV-001',
        'name' => 'Oil Change',
        'base_price' => 200,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    ServiceCatalog::query()->withoutGlobalScopes()->create([
        'branch_id' => $branches['secondary']->id,
        'code' => 'SRV-002',
        'name' => 'Engine Tuning',
        'base_price' => 300,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $response = $this->get(serviceCatalogTenantRoute('service-catalog.index'));

    $response->assertSuccessful();
    $response->assertSee('Oil Change');
    $response->assertDontSee('Engine Tuning');
});

it('shows create service catalog page with active taxes only', function (): void {
    authenticateServiceCatalogUser();

    Tax::query()->create([
        'code' => 'ACT',
        'name' => 'Active Tax',
        'rate' => 15,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    Tax::query()->create([
        'code' => 'INA',
        'name' => 'Inactive Tax',
        'rate' => 10,
        'status' => RecordStatus::INACTIVE->value,
    ]);

    $response = $this->get(serviceCatalogTenantRoute('service-catalog.create'));

    $response->assertSuccessful();
    $response->assertSee('Active Tax');
    $response->assertDontSee('Inactive Tax');
});

it('stores service catalog for current branch', function (): void {
    $branches = authenticateServiceCatalogUser();

    $tax = Tax::query()->create([
        'code' => 'GST',
        'name' => 'GST',
        'rate' => 17,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $response = $this->post(serviceCatalogTenantRoute('service-catalog.store'), [
        'code' => 'SRV-NEW',
        'name' => 'Wheel Alignment',
        'category' => 'Workshop',
        'default_tax_id' => $tax->id,
        'base_price' => 250,
        'duration_minutes' => 45,
        'is_taxable' => '1',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $response->assertRedirect(serviceCatalogTenantRoute('service-catalog.index'));
    $response->assertSessionHas('status', 'Created.');

    $this->assertDatabaseHas('service_catalog', [
        'branch_id' => $branches['current']->id,
        'code' => 'SRV-NEW',
        'name' => 'Wheel Alignment',
        'default_tax_id' => $tax->id,
        'base_price' => 250,
        'duration_minutes' => 45,
        'is_taxable' => 1,
        'status' => RecordStatus::ACTIVE->value,
    ], 'tenant');
});

it('validates required service catalog fields', function (string $field): void {
    authenticateServiceCatalogUser();

    $payload = [
        'code' => 'SRV-VAL',
        'name' => 'Validation Service',
        'base_price' => 100,
        'status' => RecordStatus::ACTIVE->value,
    ];

    unset($payload[$field]);

    $response = $this->from(serviceCatalogTenantRoute('service-catalog.create'))
        ->post(serviceCatalogTenantRoute('service-catalog.store'), $payload);

    $response->assertRedirect(serviceCatalogTenantRoute('service-catalog.create'));
    $response->assertSessionHasErrors([$field]);
})->with([
    'code' => 'code',
    'name' => 'name',
    'base_price' => 'base_price',
    'status' => 'status',
]);

it('validates unique service code per branch', function (): void {
    $branches = authenticateServiceCatalogUser();

    ServiceCatalog::query()->withoutGlobalScopes()->create([
        'branch_id' => $branches['current']->id,
        'code' => 'SRV-DUP',
        'name' => 'Existing Service',
        'base_price' => 99,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $response = $this->from(serviceCatalogTenantRoute('service-catalog.create'))
        ->post(serviceCatalogTenantRoute('service-catalog.store'), [
            'code' => 'SRV-DUP',
            'name' => 'Duplicate Service',
            'base_price' => 120,
            'status' => RecordStatus::ACTIVE->value,
        ]);

    $response->assertRedirect(serviceCatalogTenantRoute('service-catalog.create'));
    $response->assertSessionHasErrors(['code' => 'This service code already exists for the current branch.']);
});

it('allows same service code in different branch', function (): void {
    $branches = authenticateServiceCatalogUser();

    ServiceCatalog::query()->withoutGlobalScopes()->create([
        'branch_id' => $branches['secondary']->id,
        'code' => 'SRV-COM',
        'name' => 'Other Branch Service',
        'base_price' => 80,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $response = $this->post(serviceCatalogTenantRoute('service-catalog.store'), [
        'code' => 'SRV-COM',
        'name' => 'Current Branch Service',
        'base_price' => 90,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $response->assertRedirect(serviceCatalogTenantRoute('service-catalog.index'));

    $count = ServiceCatalog::query()->withoutGlobalScopes()->where('code', 'SRV-COM')->count();
    expect($count)->toBe(2);
});

it('clamps service catalog pagination limits', function (): void {
    authenticateServiceCatalogUser();

    $minResponse = $this->get(serviceCatalogTenantRoute('service-catalog.index', ['per_page' => 1]));
    $maxResponse = $this->get(serviceCatalogTenantRoute('service-catalog.index', ['per_page' => 999]));

    expect($minResponse->viewData('items')->perPage())->toBe(5);
    expect($maxResponse->viewData('items')->perPage())->toBe(100);
});

it('validates service catalog enum and duration limits', function (): void {
    authenticateServiceCatalogUser();

    $response = $this->from(serviceCatalogTenantRoute('service-catalog.create'))
        ->post(serviceCatalogTenantRoute('service-catalog.store'), [
            'code' => 'SRV-LIMIT',
            'name' => 'Limit Service',
            'base_price' => 100,
            'duration_minutes' => 0,
            'status' => 'bad-status',
        ]);

    $response->assertRedirect(serviceCatalogTenantRoute('service-catalog.create'));
    $response->assertSessionHasErrors(['duration_minutes', 'status']);
});

it('shows service catalog details for current branch', function (): void {
    $branches = authenticateServiceCatalogUser();

    $service = ServiceCatalog::query()->withoutGlobalScopes()->create([
        'branch_id' => $branches['current']->id,
        'code' => 'SRV-SHOW',
        'name' => 'Show Service',
        'base_price' => 120,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    session()->put('tenant.current_branch_id', $branches['current']->id);
    $response = (new ServiceCatalogController())->show($service);

    expect($response->name())->toBe('tenants.service-catalog.show');
    expect($response->getData()['serviceCatalog']->id)->toBe($service->id);
});

it('shows edit service catalog page for current branch', function (): void {
    $branches = authenticateServiceCatalogUser();

    $service = ServiceCatalog::query()->withoutGlobalScopes()->create([
        'branch_id' => $branches['current']->id,
        'code' => 'SRV-EDIT',
        'name' => 'Edit Service',
        'base_price' => 180,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    session()->put('tenant.current_branch_id', $branches['current']->id);
    $response = (new ServiceCatalogController())->edit($service);

    expect($response->name())->toBe('tenants.service-catalog.edit');
    expect($response->getData()['serviceCatalog']->id)->toBe($service->id);
});

it('deletes service catalog in current branch', function (): void {
    $branches = authenticateServiceCatalogUser();

    $service = ServiceCatalog::query()->withoutGlobalScopes()->create([
        'branch_id' => $branches['current']->id,
        'code' => 'SRV-DEL',
        'name' => 'Delete Service',
        'base_price' => 220,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    session()->put('tenant.current_branch_id', $branches['current']->id);
    $response = (new ServiceCatalogController())->destroy($service);

    expect($response->getTargetUrl())->toBe(serviceCatalogTenantRoute('service-catalog.index'));
    expect($response->getSession()->get('status'))->toBe('Deleted.');
    $this->assertDatabaseMissing('service_catalog', ['id' => $service->id], 'tenant');
});

it('throws not found when showing service catalog outside current branch', function (): void {
    $branches = authenticateServiceCatalogUser();

    $foreignService = ServiceCatalog::query()->withoutGlobalScopes()->create([
        'branch_id' => $branches['secondary']->id,
        'code' => 'SRV-ALT-404',
        'name' => 'Foreign Service',
        'base_price' => 90,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $this->expectException(NotFoundHttpException::class);
    (new ServiceCatalogController())->show($foreignService);
});

<?php

declare(strict_types=1);

use App\Enums\BranchStatus;
use App\Enums\CustomerStatus;
use App\Enums\JobCardServiceStatus;
use App\Enums\JobCardStatus;
use App\Enums\RecordStatus;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerVehicle;
use App\Models\JobCard;
use App\Models\JobCardService;
use App\Models\ServiceCatalog;
use App\Models\Tax;
use App\Models\User;
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

function jobCardServicesTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

/**
 * @return array{current: Branch, secondary: Branch, currentJobCard: JobCard, secondaryJobCard: JobCard, currentCatalog: ServiceCatalog, secondaryCatalog: ServiceCatalog, currentTech: User, secondaryTech: User}
 */
function authenticateJobCardServiceUser(): array
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

    $currentTech = User::query()->create([
        'branch_id' => $currentBranch->id,
        'name' => 'Main Tech',
        'email' => 'jc.service.main+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => 'active',
    ]);

    $secondaryTech = User::query()->create([
        'branch_id' => $secondaryBranch->id,
        'name' => 'Alt Tech',
        'email' => 'jc.service.alt+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => 'active',
    ]);

    $mainCustomer = Customer::query()->withoutGlobalScopes()->create([
        'branch_id' => $currentBranch->id,
        'code' => 'CUST-M',
        'name' => 'Main Customer',
        'status' => CustomerStatus::ACTIVE->value,
    ]);

    $altCustomer = Customer::query()->withoutGlobalScopes()->create([
        'branch_id' => $secondaryBranch->id,
        'code' => 'CUST-A',
        'name' => 'Alt Customer',
        'status' => CustomerStatus::ACTIVE->value,
    ]);

    $mainVehicle = CustomerVehicle::query()->create([
        'customer_id' => $mainCustomer->id,
        'registration_no' => 'MS-100',
    ]);

    $altVehicle = CustomerVehicle::query()->create([
        'customer_id' => $altCustomer->id,
        'registration_no' => 'AS-100',
    ]);

    $currentJobCard = JobCard::query()->withoutGlobalScopes()->create([
        'branch_id' => $currentBranch->id,
        'customer_id' => $mainCustomer->id,
        'vehicle_id' => $mainVehicle->id,
        'job_no' => 'JC-SVC-M',
        'job_date' => now()->toDateString(),
        'status' => JobCardStatus::NEW->value,
    ]);

    $secondaryJobCard = JobCard::query()->withoutGlobalScopes()->create([
        'branch_id' => $secondaryBranch->id,
        'customer_id' => $altCustomer->id,
        'vehicle_id' => $altVehicle->id,
        'job_no' => 'JC-SVC-A',
        'job_date' => now()->toDateString(),
        'status' => JobCardStatus::NEW->value,
    ]);

    $tax = Tax::query()->create([
        'code' => 'GST',
        'name' => 'GST',
        'rate' => 17,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $currentCatalog = ServiceCatalog::query()->withoutGlobalScopes()->create([
        'branch_id' => $currentBranch->id,
        'default_tax_id' => $tax->id,
        'code' => 'SC-M',
        'name' => 'Main Service',
        'base_price' => 200,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $secondaryCatalog = ServiceCatalog::query()->withoutGlobalScopes()->create([
        'branch_id' => $secondaryBranch->id,
        'default_tax_id' => $tax->id,
        'code' => 'SC-A',
        'name' => 'Alt Service',
        'base_price' => 200,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    test()->actingAs($currentTech, 'user');
    test()->withSession(['tenant.current_branch_id' => $currentBranch->id]);

    return [
        'current' => $currentBranch,
        'secondary' => $secondaryBranch,
        'currentJobCard' => $currentJobCard,
        'secondaryJobCard' => $secondaryJobCard,
        'currentCatalog' => $currentCatalog,
        'secondaryCatalog' => $secondaryCatalog,
        'currentTech' => $currentTech,
        'secondaryTech' => $secondaryTech,
    ];
}

it('shows job card services index for current branch job cards', function (): void {
    $fixture = authenticateJobCardServiceUser();

    JobCardService::query()->create([
        'job_card_id' => $fixture['currentJobCard']->id,
        'service_name' => 'Main Line',
        'qty' => 1,
        'rate' => 100,
        'line_total' => 100,
        'status' => JobCardServiceStatus::PENDING->value,
    ]);

    JobCardService::query()->create([
        'job_card_id' => $fixture['secondaryJobCard']->id,
        'service_name' => 'Alt Line',
        'qty' => 1,
        'rate' => 100,
        'line_total' => 100,
        'status' => JobCardServiceStatus::PENDING->value,
    ]);

    $response = $this->get(jobCardServicesTenantRoute('job-card-services.index'));

    $response->assertSuccessful();

    $names = $response->viewData('items')->getCollection()->pluck('service_name')->all();
    expect($names)->toContain('Main Line');
    expect($names)->not->toContain('Alt Line');
    $response->assertSee('data-ajax-table-search', false);
    $response->assertSee('job-card-services-search-form');
    $response->assertSee('job-card-services-search-loading');
});

it('filters job card services by search keyword', function (): void {
    $fixture = authenticateJobCardServiceUser();

    JobCardService::query()->create([
        'job_card_id' => $fixture['currentJobCard']->id,
        'service_name' => 'Axle Service',
        'qty' => 1,
        'rate' => 100,
        'line_total' => 100,
        'status' => JobCardServiceStatus::PENDING->value,
    ]);

    JobCardService::query()->create([
        'job_card_id' => $fixture['currentJobCard']->id,
        'service_name' => 'Brake Service',
        'qty' => 1,
        'rate' => 100,
        'line_total' => 100,
        'status' => JobCardServiceStatus::PENDING->value,
    ]);

    $response = $this->get(jobCardServicesTenantRoute('job-card-services.index', ['search' => 'Axle']));

    $response->assertSuccessful();
    $names = $response->viewData('items')->getCollection()->pluck('service_name')->all();
    expect($names)->toContain('Axle Service');
    expect($names)->not->toContain('Brake Service');
});

it('shows create job card services page with current branch options', function (): void {
    $fixture = authenticateJobCardServiceUser();

    $response = $this->get(jobCardServicesTenantRoute('job-card-services.create'));

    $response->assertSuccessful();

    expect($response->viewData('jobCards')->pluck('id')->all())->toContain($fixture['currentJobCard']->id);
    expect($response->viewData('jobCards')->pluck('id')->all())->not->toContain($fixture['secondaryJobCard']->id);
    expect($response->viewData('serviceCatalogs')->pluck('id')->all())->toContain($fixture['currentCatalog']->id);
    expect($response->viewData('serviceCatalogs')->pluck('id')->all())->not->toContain($fixture['secondaryCatalog']->id);
    expect($response->viewData('technicians')->pluck('id')->all())->toContain($fixture['currentTech']->id);
    expect($response->viewData('technicians')->pluck('id')->all())->not->toContain($fixture['secondaryTech']->id);
});

it('stores job card service and computes line total', function (): void {
    $fixture = authenticateJobCardServiceUser();

    $response = $this->post(jobCardServicesTenantRoute('job-card-services.store'), [
        'job_card_id' => $fixture['currentJobCard']->id,
        'service_catalog_id' => $fixture['currentCatalog']->id,
        'technician_id' => $fixture['currentTech']->id,
        'service_name' => 'Oil Change',
        'qty' => 2,
        'rate' => 150,
        'status' => JobCardServiceStatus::DONE->value,
    ]);

    $response->assertRedirect(jobCardServicesTenantRoute('job-card-services.index'));
    $response->assertSessionHas('status', 'Created.');

    $this->assertDatabaseHas('job_card_services', [
        'job_card_id' => $fixture['currentJobCard']->id,
        'service_catalog_id' => $fixture['currentCatalog']->id,
        'technician_id' => $fixture['currentTech']->id,
        'service_name' => 'Oil Change',
        'qty' => 2,
        'rate' => 150,
        'line_total' => 300,
        'status' => JobCardServiceStatus::DONE->value,
    ], 'tenant');
});

it('validates required job card service fields', function (string $field): void {
    $fixture = authenticateJobCardServiceUser();

    $payload = [
        'job_card_id' => $fixture['currentJobCard']->id,
        'service_name' => 'Required Service',
        'qty' => 1,
        'rate' => 100,
        'status' => JobCardServiceStatus::PENDING->value,
    ];

    unset($payload[$field]);

    $response = $this->from(jobCardServicesTenantRoute('job-card-services.create'))
        ->post(jobCardServicesTenantRoute('job-card-services.store'), $payload);

    $response->assertRedirect(jobCardServicesTenantRoute('job-card-services.create'));
    $response->assertSessionHasErrors([$field]);
})->with([
    'job_card_id' => 'job_card_id',
    'service_name' => 'service_name',
    'qty' => 'qty',
    'rate' => 'rate',
    'status' => 'status',
]);

it('validates branch scoped relationships in job card service', function (): void {
    $fixture = authenticateJobCardServiceUser();

    $response = $this->from(jobCardServicesTenantRoute('job-card-services.create'))
        ->post(jobCardServicesTenantRoute('job-card-services.store'), [
            'job_card_id' => $fixture['secondaryJobCard']->id,
            'service_catalog_id' => $fixture['secondaryCatalog']->id,
            'technician_id' => $fixture['secondaryTech']->id,
            'service_name' => 'Scope Fail',
            'qty' => 1,
            'rate' => 100,
            'status' => JobCardServiceStatus::PENDING->value,
        ]);

    $response->assertRedirect(jobCardServicesTenantRoute('job-card-services.create'));
    $response->assertSessionHasErrors(['job_card_id', 'service_catalog_id', 'technician_id']);
});

it('validates qty greater than zero and non negative rate', function (): void {
    $fixture = authenticateJobCardServiceUser();

    $response = $this->from(jobCardServicesTenantRoute('job-card-services.create'))
        ->post(jobCardServicesTenantRoute('job-card-services.store'), [
            'job_card_id' => $fixture['currentJobCard']->id,
            'service_name' => 'Numeric Service',
            'qty' => 0,
            'rate' => -1,
            'status' => JobCardServiceStatus::PENDING->value,
        ]);

    $response->assertRedirect(jobCardServicesTenantRoute('job-card-services.create'));
    $response->assertSessionHasErrors(['qty', 'rate']);
});

it('clamps job card services pagination limits', function (): void {
    authenticateJobCardServiceUser();

    $minResponse = $this->get(jobCardServicesTenantRoute('job-card-services.index', ['per_page' => 1]));
    $maxResponse = $this->get(jobCardServicesTenantRoute('job-card-services.index', ['per_page' => 999]));

    expect($minResponse->viewData('items')->perPage())->toBe(5);
    expect($maxResponse->viewData('items')->perPage())->toBe(100);
});

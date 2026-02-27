<?php

declare(strict_types=1);

use App\Actions\Tenant\JobCard\DeleteJobCardAction;
use App\Actions\Tenant\JobCard\EnsureJobCardInBranchAction;
use App\Actions\Tenant\JobCard\SyncJobCardLinesAction;
use App\Actions\Tenant\JobCard\UpdateJobCardAction;
use App\Enums\BranchStatus;
use App\Enums\CustomerStatus;
use App\Enums\JobCardServiceStatus;
use App\Enums\JobCardStatus;
use App\Enums\RecordStatus;
use App\Http\Controllers\Tenant\JobCardController;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerVehicle;
use App\Models\JobCard;
use App\Models\Product;
use App\Models\ServiceCatalog;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
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

function jobCardsTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

/**
 * @return array{current: Branch, secondary: Branch, currentCustomer: Customer, currentVehicle: CustomerVehicle, currentEmployee: User, secondaryCustomer: Customer, secondaryVehicle: CustomerVehicle, secondaryEmployee: User, serviceCatalog: ServiceCatalog, product: Product}
 */
function authenticateJobCardUser(): array
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

    $currentEmployee = User::query()->create([
        'branch_id' => $currentBranch->id,
        'name' => 'Main Technician',
        'email' => 'tech.main+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => 'active',
    ]);

    $secondaryEmployee = User::query()->create([
        'branch_id' => $secondaryBranch->id,
        'name' => 'Alt Technician',
        'email' => 'tech.alt+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => 'active',
    ]);

    $currentCustomer = Customer::query()->withoutGlobalScopes()->create([
        'branch_id' => $currentBranch->id,
        'code' => 'CUST-MAIN',
        'name' => 'Main Customer',
        'status' => CustomerStatus::ACTIVE->value,
    ]);

    $secondaryCustomer = Customer::query()->withoutGlobalScopes()->create([
        'branch_id' => $secondaryBranch->id,
        'code' => 'CUST-ALT',
        'name' => 'Alt Customer',
        'status' => CustomerStatus::ACTIVE->value,
    ]);

    $currentVehicle = CustomerVehicle::query()->create([
        'customer_id' => $currentCustomer->id,
        'registration_no' => 'MAIN-001',
        'model' => 'Corolla',
    ]);

    $secondaryVehicle = CustomerVehicle::query()->create([
        'customer_id' => $secondaryCustomer->id,
        'registration_no' => 'ALT-001',
        'model' => 'Civic',
    ]);

    $serviceCatalog = ServiceCatalog::query()->withoutGlobalScopes()->create([
        'branch_id' => $currentBranch->id,
        'code' => 'SVC-MAIN-1',
        'name' => 'Inspection',
        'category' => 'Workshop',
        'base_price' => 150,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $product = Product::query()->create([
        'sku' => 'PART-MAIN-1',
        'name' => 'Oil Filter',
        'track_stock' => true,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    test()->actingAs($currentEmployee, 'user');
    test()->withSession(['tenant.current_branch_id' => $currentBranch->id]);

    return [
        'current' => $currentBranch,
        'secondary' => $secondaryBranch,
        'currentCustomer' => $currentCustomer,
        'currentVehicle' => $currentVehicle,
        'currentEmployee' => $currentEmployee,
        'secondaryCustomer' => $secondaryCustomer,
        'secondaryVehicle' => $secondaryVehicle,
        'secondaryEmployee' => $secondaryEmployee,
        'serviceCatalog' => $serviceCatalog,
        'product' => $product,
    ];
}

it('shows job cards index scoped to current branch', function (): void {
    $fixture = authenticateJobCardUser();

    JobCard::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'customer_id' => $fixture['currentCustomer']->id,
        'vehicle_id' => $fixture['currentVehicle']->id,
        'job_no' => 'JC-MAIN',
        'job_date' => now()->toDateString(),
        'status' => JobCardStatus::NEW->value,
    ]);

    JobCard::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['secondary']->id,
        'customer_id' => $fixture['secondaryCustomer']->id,
        'vehicle_id' => $fixture['secondaryVehicle']->id,
        'job_no' => 'JC-ALT',
        'job_date' => now()->toDateString(),
        'status' => JobCardStatus::NEW->value,
    ]);

    $response = $this->get(jobCardsTenantRoute('job-cards.index'));

    $response->assertSuccessful();
    $response->assertSee('JC-MAIN');
    $response->assertDontSee('JC-ALT');
    $response->assertSee('data-ajax-table-search', false);
    $response->assertSee('data-ajax-sort-link', false);
    $response->assertSee('job-cards-search-form');
    $response->assertSee('job-cards-search-loading');
});

it('filters job cards by customer, vehicle and job number', function (): void {
    $fixture = authenticateJobCardUser();

    JobCard::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'customer_id' => $fixture['currentCustomer']->id,
        'vehicle_id' => $fixture['currentVehicle']->id,
        'job_no' => 'JC-AXLE',
        'job_date' => now()->toDateString(),
        'status' => JobCardStatus::NEW->value,
    ]);

    JobCard::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'customer_id' => $fixture['currentCustomer']->id,
        'vehicle_id' => $fixture['currentVehicle']->id,
        'job_no' => 'JC-BRAKE',
        'job_date' => now()->toDateString(),
        'status' => JobCardStatus::NEW->value,
    ]);

    $response = $this->get(jobCardsTenantRoute('job-cards.index', ['search' => 'AXLE']));

    $response->assertSuccessful();
    $response->assertSee('JC-AXLE');
    $response->assertDontSee('JC-BRAKE');
});

it('sorts job cards by job number ascending and descending', function (): void {
    $fixture = authenticateJobCardUser();

    JobCard::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'customer_id' => $fixture['currentCustomer']->id,
        'vehicle_id' => $fixture['currentVehicle']->id,
        'job_no' => 'JC-SORT-A',
        'job_date' => now()->toDateString(),
        'status' => JobCardStatus::NEW->value,
    ]);

    JobCard::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'customer_id' => $fixture['currentCustomer']->id,
        'vehicle_id' => $fixture['currentVehicle']->id,
        'job_no' => 'JC-SORT-Z',
        'job_date' => now()->toDateString(),
        'status' => JobCardStatus::NEW->value,
    ]);

    $ascending = $this->get(jobCardsTenantRoute('job-cards.index', [
        'sort_by' => 'job_no',
        'sort_direction' => 'asc',
    ]));

    $descending = $this->get(jobCardsTenantRoute('job-cards.index', [
        'sort_by' => 'job_no',
        'sort_direction' => 'desc',
    ]));

    $ascendingJobNumbers = $ascending->viewData('items')->pluck('job_no')->values()->all();
    $descendingJobNumbers = $descending->viewData('items')->pluck('job_no')->values()->all();

    expect(array_search('JC-SORT-A', $ascendingJobNumbers, true))->toBeLessThan(array_search('JC-SORT-Z', $ascendingJobNumbers, true));
    expect(array_search('JC-SORT-A', $descendingJobNumbers, true))->toBeGreaterThan(array_search('JC-SORT-Z', $descendingJobNumbers, true));
});

it('shows create job card page with current branch options', function (): void {
    $fixture = authenticateJobCardUser();

    $response = $this->get(jobCardsTenantRoute('job-cards.create'));

    $response->assertSuccessful();

    $customers = $response->viewData('customers')->pluck('id')->all();
    $vehicles = $response->viewData('vehicles')->pluck('id')->all();
    $employees = $response->viewData('employees')->pluck('id')->all();

    expect($customers)->toContain($fixture['currentCustomer']->id);
    expect($customers)->not->toContain($fixture['secondaryCustomer']->id);
    expect($vehicles)->toContain($fixture['currentVehicle']->id);
    expect($vehicles)->not->toContain($fixture['secondaryVehicle']->id);
    expect($employees)->toContain($fixture['currentEmployee']->id);
    expect($employees)->not->toContain($fixture['secondaryEmployee']->id);
});

it('stores job card with current branch and created by user', function (): void {
    $fixture = authenticateJobCardUser();

    $response = $this->post(jobCardsTenantRoute('job-cards.store'), [
        'customer_id' => $fixture['currentCustomer']->id,
        'vehicle_id' => $fixture['currentVehicle']->id,
        'assigned_employee_id' => $fixture['currentEmployee']->id,
        'job_no' => 'JC-NEW',
        'job_date' => now()->toDateString(),
        'status' => JobCardStatus::IN_PROGRESS->value,
        'meter_reading' => 100,
    ]);

    $response->assertRedirect(jobCardsTenantRoute('job-cards.index'));
    $response->assertSessionHas('status', 'Created.');

    $this->assertDatabaseHas('job_cards', [
        'branch_id' => $fixture['current']->id,
        'customer_id' => $fixture['currentCustomer']->id,
        'vehicle_id' => $fixture['currentVehicle']->id,
        'assigned_employee_id' => $fixture['currentEmployee']->id,
        'created_by' => $fixture['currentEmployee']->id,
        'job_no' => 'JC-NEW',
        'status' => JobCardStatus::IN_PROGRESS->value,
    ], 'tenant');
});

it('stores inline service and part lines from job card form', function (): void {
    $fixture = authenticateJobCardUser();

    $response = $this->post(jobCardsTenantRoute('job-cards.store'), [
        'customer_id' => $fixture['currentCustomer']->id,
        'vehicle_id' => $fixture['currentVehicle']->id,
        'assigned_employee_id' => $fixture['currentEmployee']->id,
        'job_no' => 'JC-LINES-NEW',
        'job_date' => now()->toDateString(),
        'status' => JobCardStatus::IN_PROGRESS->value,
        'services' => [
            [
                'service_catalog_id' => $fixture['serviceCatalog']->id,
                'technician_id' => $fixture['currentEmployee']->id,
                'service_name' => 'Inspection Service',
                'qty' => 2,
                'rate' => 125,
                'status' => JobCardServiceStatus::PENDING->value,
                'remarks' => 'Initial checks',
            ],
        ],
        'parts' => [
            [
                'product_id' => $fixture['product']->id,
                'qty' => 3,
                'unit_price' => 40,
            ],
        ],
    ]);

    $response->assertRedirect(jobCardsTenantRoute('job-cards.index'));

    $jobCard = JobCard::query()->where('job_no', 'JC-LINES-NEW')->firstOrFail();

    $this->assertDatabaseHas('job_card_services', [
        'job_card_id' => $jobCard->id,
        'service_name' => 'Inspection Service',
        'line_total' => 250,
    ], 'tenant');

    $this->assertDatabaseHas('job_card_parts', [
        'job_card_id' => $jobCard->id,
        'product_id' => $fixture['product']->id,
        'line_total' => 120,
    ], 'tenant');
});

it('syncs inline service and part lines when updating a job card', function (): void {
    $fixture = authenticateJobCardUser();

    $jobCard = JobCard::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'customer_id' => $fixture['currentCustomer']->id,
        'vehicle_id' => $fixture['currentVehicle']->id,
        'assigned_employee_id' => $fixture['currentEmployee']->id,
        'job_no' => 'JC-LINES-UPD',
        'job_date' => now()->toDateString(),
        'status' => JobCardStatus::NEW->value,
    ]);

    $existingService = $jobCard->services()->create([
        'service_catalog_id' => $fixture['serviceCatalog']->id,
        'technician_id' => $fixture['currentEmployee']->id,
        'service_name' => 'Old Service',
        'qty' => 1,
        'rate' => 100,
        'line_total' => 100,
        'status' => JobCardServiceStatus::PENDING->value,
    ]);

    $existingPart = $jobCard->parts()->create([
        'product_id' => $fixture['product']->id,
        'qty' => 1,
        'unit_price' => 50,
        'line_total' => 50,
    ]);

    $payload = [
        'customer_id' => $fixture['currentCustomer']->id,
        'vehicle_id' => $fixture['currentVehicle']->id,
        'assigned_employee_id' => $fixture['currentEmployee']->id,
        'job_no' => 'JC-LINES-UPD',
        'job_date' => now()->toDateString(),
        'status' => JobCardStatus::IN_PROGRESS->value,
        'services' => [
            [
                'id' => $existingService->id,
                'service_catalog_id' => $fixture['serviceCatalog']->id,
                'technician_id' => $fixture['currentEmployee']->id,
                'service_name' => 'Updated Service',
                'qty' => 2,
                'rate' => 120,
                'status' => JobCardServiceStatus::DONE->value,
                'remarks' => 'Completed',
            ],
        ],
        'parts' => [],
    ];

    (new UpdateJobCardAction())->handle($jobCard, [
        'customer_id' => $payload['customer_id'],
        'vehicle_id' => $payload['vehicle_id'],
        'assigned_employee_id' => $payload['assigned_employee_id'],
        'job_no' => $payload['job_no'],
        'job_date' => $payload['job_date'],
        'status' => $payload['status'],
    ], $fixture['current']->id);

    (new SyncJobCardLinesAction())->handle($jobCard, $payload);

    $this->assertDatabaseHas('job_card_services', [
        'id' => $existingService->id,
        'job_card_id' => $jobCard->id,
        'service_name' => 'Updated Service',
        'line_total' => 240,
        'status' => JobCardServiceStatus::DONE->value,
    ], 'tenant');

    $this->assertDatabaseMissing('job_card_parts', [
        'id' => $existingPart->id,
    ], 'tenant');
});

it('validates required job card fields', function (string $field): void {
    $fixture = authenticateJobCardUser();

    $payload = [
        'customer_id' => $fixture['currentCustomer']->id,
        'job_no' => 'JC-REQ',
        'job_date' => now()->toDateString(),
        'status' => JobCardStatus::NEW->value,
    ];

    unset($payload[$field]);

    $response = $this->from(jobCardsTenantRoute('job-cards.create'))
        ->post(jobCardsTenantRoute('job-cards.store'), $payload);

    $response->assertRedirect(jobCardsTenantRoute('job-cards.create'));
    $response->assertSessionHasErrors([$field]);
})->with([
    'customer_id' => 'customer_id',
    'job_no' => 'job_no',
    'job_date' => 'job_date',
    'status' => 'status',
]);

it('validates branch scoped references and numeric constraints for job card', function (): void {
    $fixture = authenticateJobCardUser();

    $response = $this->from(jobCardsTenantRoute('job-cards.create'))
        ->post(jobCardsTenantRoute('job-cards.store'), [
            'customer_id' => $fixture['secondaryCustomer']->id,
            'vehicle_id' => $fixture['secondaryVehicle']->id,
            'assigned_employee_id' => $fixture['secondaryEmployee']->id,
            'job_no' => 'JC-SCOPE',
            'job_date' => now()->toDateString(),
            'status' => JobCardStatus::NEW->value,
            'meter_reading' => -1,
            'out_time' => now()->subHour()->toDateTimeString(),
            'in_time' => now()->toDateTimeString(),
        ]);

    $response->assertRedirect(jobCardsTenantRoute('job-cards.create'));
    $response->assertSessionHasErrors(['customer_id', 'vehicle_id', 'assigned_employee_id', 'meter_reading', 'out_time']);
});

it('validates job number uniqueness per branch', function (): void {
    $fixture = authenticateJobCardUser();

    JobCard::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'customer_id' => $fixture['currentCustomer']->id,
        'vehicle_id' => $fixture['currentVehicle']->id,
        'job_no' => 'JC-DUP',
        'job_date' => now()->toDateString(),
        'status' => JobCardStatus::NEW->value,
    ]);

    $response = $this->from(jobCardsTenantRoute('job-cards.create'))
        ->post(jobCardsTenantRoute('job-cards.store'), [
            'customer_id' => $fixture['currentCustomer']->id,
            'job_no' => 'JC-DUP',
            'job_date' => now()->toDateString(),
            'status' => JobCardStatus::NEW->value,
        ]);

    $response->assertRedirect(jobCardsTenantRoute('job-cards.create'));
    $response->assertSessionHasErrors(['job_no']);
});

it('allows same job number in different branch', function (): void {
    $fixture = authenticateJobCardUser();

    JobCard::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['secondary']->id,
        'customer_id' => $fixture['secondaryCustomer']->id,
        'vehicle_id' => $fixture['secondaryVehicle']->id,
        'job_no' => 'JC-SHARED',
        'job_date' => now()->toDateString(),
        'status' => JobCardStatus::NEW->value,
    ]);

    $response = $this->post(jobCardsTenantRoute('job-cards.store'), [
        'customer_id' => $fixture['currentCustomer']->id,
        'job_no' => 'JC-SHARED',
        'job_date' => now()->toDateString(),
        'status' => JobCardStatus::NEW->value,
    ]);

    $response->assertRedirect(jobCardsTenantRoute('job-cards.index'));

    $count = JobCard::query()->withoutGlobalScopes()->where('job_no', 'JC-SHARED')->count();
    expect($count)->toBe(2);
});

it('validates uuid format for ids in job card request', function (): void {
    authenticateJobCardUser();

    $response = $this->from(jobCardsTenantRoute('job-cards.create'))
        ->post(jobCardsTenantRoute('job-cards.store'), [
            'customer_id' => 'not-a-uuid',
            'vehicle_id' => (string) Str::uuid(),
            'job_no' => 'JC-UUID',
            'job_date' => now()->toDateString(),
            'status' => JobCardStatus::NEW->value,
        ]);

    $response->assertRedirect(jobCardsTenantRoute('job-cards.create'));
    $response->assertSessionHasErrors(['customer_id']);
});

it('clamps job cards pagination limits', function (): void {
    authenticateJobCardUser();

    $minResponse = $this->get(jobCardsTenantRoute('job-cards.index', ['per_page' => 1]));
    $maxResponse = $this->get(jobCardsTenantRoute('job-cards.index', ['per_page' => 999]));

    expect($minResponse->viewData('items')->perPage())->toBe(5);
    expect($maxResponse->viewData('items')->perPage())->toBe(100);
});

it('shows job card details in current branch', function (): void {
    $fixture = authenticateJobCardUser();

    $jobCard = JobCard::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'customer_id' => $fixture['currentCustomer']->id,
        'vehicle_id' => $fixture['currentVehicle']->id,
        'assigned_employee_id' => $fixture['currentEmployee']->id,
        'job_no' => 'JC-SHOW',
        'job_date' => now()->toDateString(),
        'status' => JobCardStatus::NEW->value,
    ]);

    session()->put('tenant.current_branch_id', $fixture['current']->id);
    $response = (new JobCardController())->show($jobCard, new EnsureJobCardInBranchAction());

    expect($response->name())->toBe('tenants.job-cards.show');
    expect($response->getData()['jobCard']->id)->toBe($jobCard->id);
});

it('shows edit job card page for current branch record', function (): void {
    $fixture = authenticateJobCardUser();

    $jobCard = JobCard::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'customer_id' => $fixture['currentCustomer']->id,
        'vehicle_id' => $fixture['currentVehicle']->id,
        'assigned_employee_id' => $fixture['currentEmployee']->id,
        'job_no' => 'JC-EDIT',
        'job_date' => now()->toDateString(),
        'status' => JobCardStatus::NEW->value,
    ]);

    session()->put('tenant.current_branch_id', $fixture['current']->id);
    $response = (new JobCardController())->edit($jobCard, new EnsureJobCardInBranchAction());

    expect($response->name())->toBe('tenants.job-cards.edit');
    expect($response->getData()['jobCard']->id)->toBe($jobCard->id);
});

it('accepts out_time equal to in_time and validates total_visits minimum', function (): void {
    $fixture = authenticateJobCardUser();
    $timestamp = now()->toDateTimeString();

    $okResponse = $this->post(jobCardsTenantRoute('job-cards.store'), [
        'customer_id' => $fixture['currentCustomer']->id,
        'job_no' => 'JC-TIME-OK',
        'job_date' => now()->toDateString(),
        'status' => JobCardStatus::NEW->value,
        'in_time' => $timestamp,
        'out_time' => $timestamp,
    ]);
    $okResponse->assertRedirect(jobCardsTenantRoute('job-cards.index'));

    $badResponse = $this->from(jobCardsTenantRoute('job-cards.create'))
        ->post(jobCardsTenantRoute('job-cards.store'), [
            'customer_id' => $fixture['currentCustomer']->id,
            'job_no' => 'JC-VISITS-BAD',
            'job_date' => now()->toDateString(),
            'status' => JobCardStatus::NEW->value,
            'total_visits' => -1,
        ]);

    $badResponse->assertRedirect(jobCardsTenantRoute('job-cards.create'));
    $badResponse->assertSessionHasErrors(['total_visits']);
});

it('deletes job card', function (): void {
    $fixture = authenticateJobCardUser();

    $jobCard = JobCard::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'customer_id' => $fixture['currentCustomer']->id,
        'vehicle_id' => $fixture['currentVehicle']->id,
        'job_no' => 'JC-DEL',
        'job_date' => now()->toDateString(),
        'status' => JobCardStatus::NEW->value,
    ]);

    session()->put('tenant.current_branch_id', $fixture['current']->id);
    $response = (new JobCardController())->destroy(
        $jobCard,
        new DeleteJobCardAction(),
        new EnsureJobCardInBranchAction()
    );

    expect($response->getTargetUrl())->toBe(jobCardsTenantRoute('job-cards.index'));
    expect($response->getSession()->get('status'))->toBe('Deleted.');
    $this->assertDatabaseMissing('job_cards', ['id' => $jobCard->id], 'tenant');
});

it('throws not found when showing job card outside current branch', function (): void {
    $fixture = authenticateJobCardUser();

    $foreignJobCard = JobCard::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['secondary']->id,
        'customer_id' => $fixture['secondaryCustomer']->id,
        'vehicle_id' => $fixture['secondaryVehicle']->id,
        'job_no' => 'JC-ALT-404',
        'job_date' => now()->toDateString(),
        'status' => JobCardStatus::NEW->value,
    ]);

    $this->expectException(NotFoundHttpException::class);
    (new JobCardController())->show($foreignJobCard, new EnsureJobCardInBranchAction());
});

<?php

declare(strict_types=1);

use App\Enums\BranchStatus;
use App\Enums\CustomerStatus;
use App\Enums\JobCardStatus;
use App\Enums\RecordStatus;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerVehicle;
use App\Models\JobCard;
use App\Models\JobCardPart;
use App\Models\Product;
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

function jobCardPartsTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

/**
 * @return array{current: Branch, secondary: Branch, currentJobCard: JobCard, secondaryJobCard: JobCard, productA: Product}
 */
function authenticateJobCardPartUser(): array
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

    $currentUser = User::query()->create([
        'branch_id' => $currentBranch->id,
        'name' => 'Main User',
        'email' => 'jc.parts.main+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => 'active',
    ]);

    User::query()->create([
        'branch_id' => $secondaryBranch->id,
        'name' => 'Alt User',
        'email' => 'jc.parts.alt+'.uniqid('', true).'@example.test',
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
        'registration_no' => 'MP-100',
    ]);

    $altVehicle = CustomerVehicle::query()->create([
        'customer_id' => $altCustomer->id,
        'registration_no' => 'AP-100',
    ]);

    $currentJobCard = JobCard::query()->withoutGlobalScopes()->create([
        'branch_id' => $currentBranch->id,
        'customer_id' => $mainCustomer->id,
        'vehicle_id' => $mainVehicle->id,
        'job_no' => 'JC-PART-M',
        'job_date' => now()->toDateString(),
        'status' => JobCardStatus::NEW->value,
    ]);

    $secondaryJobCard = JobCard::query()->withoutGlobalScopes()->create([
        'branch_id' => $secondaryBranch->id,
        'customer_id' => $altCustomer->id,
        'vehicle_id' => $altVehicle->id,
        'job_no' => 'JC-PART-A',
        'job_date' => now()->toDateString(),
        'status' => JobCardStatus::NEW->value,
    ]);

    $category = Category::query()->create([
        'name' => 'General',
        'slug' => 'general',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $productA = Product::query()->create([
        'category_id' => $category->id,
        'sku' => 'PART-001',
        'name' => 'Oil Filter',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    test()->actingAs($currentUser, 'user');
    test()->withSession(['tenant.current_branch_id' => $currentBranch->id]);

    return [
        'current' => $currentBranch,
        'secondary' => $secondaryBranch,
        'currentJobCard' => $currentJobCard,
        'secondaryJobCard' => $secondaryJobCard,
        'productA' => $productA,
    ];
}

it('shows job card parts index for current branch job cards', function (): void {
    $fixture = authenticateJobCardPartUser();

    JobCardPart::query()->create([
        'job_card_id' => $fixture['currentJobCard']->id,
        'product_id' => $fixture['productA']->id,
        'qty' => 1,
        'unit_price' => 50,
        'line_total' => 50,
    ]);

    JobCardPart::query()->create([
        'job_card_id' => $fixture['secondaryJobCard']->id,
        'product_id' => $fixture['productA']->id,
        'qty' => 1,
        'unit_price' => 70,
        'line_total' => 70,
    ]);

    $response = $this->get(jobCardPartsTenantRoute('job-card-parts.index'));

    $response->assertSuccessful();

    $lineTotals = $response->viewData('items')->getCollection()->pluck('line_total')->map(fn ($v) => (float) $v)->all();
    expect($lineTotals)->toContain(50.0);
    expect($lineTotals)->not->toContain(70.0);
});

it('shows create job card parts page with branch job cards', function (): void {
    $fixture = authenticateJobCardPartUser();

    $response = $this->get(jobCardPartsTenantRoute('job-card-parts.create'));

    $response->assertSuccessful();

    $jobCards = $response->viewData('jobCards')->pluck('id')->all();
    expect($jobCards)->toContain($fixture['currentJobCard']->id);
    expect($jobCards)->not->toContain($fixture['secondaryJobCard']->id);
});

it('stores job card part and computes line total', function (): void {
    $fixture = authenticateJobCardPartUser();

    $response = $this->post(jobCardPartsTenantRoute('job-card-parts.store'), [
        'job_card_id' => $fixture['currentJobCard']->id,
        'product_id' => $fixture['productA']->id,
        'qty' => 2,
        'unit_price' => 125,
    ]);

    $response->assertRedirect(jobCardPartsTenantRoute('job-card-parts.index'));
    $response->assertSessionHas('status', 'Created.');

    $this->assertDatabaseHas('job_card_parts', [
        'job_card_id' => $fixture['currentJobCard']->id,
        'product_id' => $fixture['productA']->id,
        'qty' => 2,
        'unit_price' => 125,
        'line_total' => 250,
    ], 'tenant');
});

it('validates required job card part fields', function (string $field): void {
    $fixture = authenticateJobCardPartUser();

    $payload = [
        'job_card_id' => $fixture['currentJobCard']->id,
        'product_id' => $fixture['productA']->id,
        'qty' => 1,
        'unit_price' => 100,
    ];

    unset($payload[$field]);

    $response = $this->from(jobCardPartsTenantRoute('job-card-parts.create'))
        ->post(jobCardPartsTenantRoute('job-card-parts.store'), $payload);

    $response->assertRedirect(jobCardPartsTenantRoute('job-card-parts.create'));
    $response->assertSessionHasErrors([$field]);
})->with([
    'job_card_id' => 'job_card_id',
    'product_id' => 'product_id',
    'qty' => 'qty',
    'unit_price' => 'unit_price',
]);

it('validates job card belongs to current branch for parts', function (): void {
    $fixture = authenticateJobCardPartUser();

    $response = $this->from(jobCardPartsTenantRoute('job-card-parts.create'))
        ->post(jobCardPartsTenantRoute('job-card-parts.store'), [
            'job_card_id' => $fixture['secondaryJobCard']->id,
            'product_id' => $fixture['productA']->id,
            'qty' => 1,
            'unit_price' => 100,
        ]);

    $response->assertRedirect(jobCardPartsTenantRoute('job-card-parts.create'));
    $response->assertSessionHasErrors(['job_card_id']);
});

it('validates qty greater than zero and non negative unit price', function (): void {
    $fixture = authenticateJobCardPartUser();

    $response = $this->from(jobCardPartsTenantRoute('job-card-parts.create'))
        ->post(jobCardPartsTenantRoute('job-card-parts.store'), [
            'job_card_id' => $fixture['currentJobCard']->id,
            'product_id' => $fixture['productA']->id,
            'qty' => 0,
            'unit_price' => -1,
        ]);

    $response->assertRedirect(jobCardPartsTenantRoute('job-card-parts.create'));
    $response->assertSessionHasErrors(['qty', 'unit_price']);
});

it('clamps job card parts pagination limits', function (): void {
    authenticateJobCardPartUser();

    $minResponse = $this->get(jobCardPartsTenantRoute('job-card-parts.index', ['per_page' => 1]));
    $maxResponse = $this->get(jobCardPartsTenantRoute('job-card-parts.index', ['per_page' => 999]));

    expect($minResponse->viewData('items')->perPage())->toBe(5);
    expect($maxResponse->viewData('items')->perPage())->toBe(100);
});

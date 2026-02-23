<?php

declare(strict_types=1);

use App\Actions\Tenant\CustomerVehicle\DeleteCustomerVehicleAction;
use App\Actions\Tenant\CustomerVehicle\EnsureVehicleInBranchAction;
use App\Enums\BranchStatus;
use App\Enums\CustomerStatus;
use App\Http\Controllers\Tenant\CustomerVehicleController;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerVehicle;
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

function vehiclesTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

/**
 * @return array{current: Branch, secondary: Branch, currentCustomer: Customer, secondaryCustomer: Customer}
 */
function authenticateVehicleUser(): array
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

    $currentCustomer = Customer::query()->withoutGlobalScopes()->create([
        'branch_id' => $currentBranch->id,
        'code' => 'CUST-MAIN',
        'name' => 'Main Customer',
        'status' => CustomerStatus::ACTIVE->value,
    ]);

    $secondaryCustomer = Customer::query()->withoutGlobalScopes()->create([
        'branch_id' => $secondaryBranch->id,
        'code' => 'CUST-ALT',
        'name' => 'Alternate Customer',
        'status' => CustomerStatus::ACTIVE->value,
    ]);

    $user = User::query()->create([
        'branch_id' => $currentBranch->id,
        'name' => 'Vehicle User',
        'email' => 'vehicle.user+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => 'active',
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $currentBranch->id]);

    return [
        'current' => $currentBranch,
        'secondary' => $secondaryBranch,
        'currentCustomer' => $currentCustomer,
        'secondaryCustomer' => $secondaryCustomer,
    ];
}

it('shows customer vehicles index scoped by customer branch', function (): void {
    $fixture = authenticateVehicleUser();

    CustomerVehicle::query()->create([
        'customer_id' => $fixture['currentCustomer']->id,
        'registration_no' => 'ABC-123',
        'model' => 'Corolla',
    ]);

    CustomerVehicle::query()->create([
        'customer_id' => $fixture['secondaryCustomer']->id,
        'registration_no' => 'XYZ-999',
        'model' => 'Civic',
    ]);

    $response = $this->get(vehiclesTenantRoute('customer-vehicles.index'));

    $response->assertSuccessful();

    $registrationNumbers = $response->viewData('items')
        ->getCollection()
        ->pluck('registration_no')
        ->all();

    expect($registrationNumbers)->toContain('ABC-123');
    expect($registrationNumbers)->not->toContain('XYZ-999');
});

it('filters customer vehicles by search', function (): void {
    $fixture = authenticateVehicleUser();

    CustomerVehicle::query()->create([
        'customer_id' => $fixture['currentCustomer']->id,
        'registration_no' => 'AAA-111',
        'model' => 'Prius',
        'chassis_no' => 'CH-PRIUS',
    ]);

    CustomerVehicle::query()->create([
        'customer_id' => $fixture['currentCustomer']->id,
        'registration_no' => 'BBB-222',
        'model' => 'Yaris',
        'chassis_no' => 'CH-YARIS',
    ]);

    $response = $this->get(vehiclesTenantRoute('customer-vehicles.index', ['search' => 'PRIUS']));

    $response->assertSuccessful();

    $models = $response->viewData('items')
        ->getCollection()
        ->pluck('model')
        ->all();

    expect($models)->toContain('Prius');
    expect($models)->not->toContain('Yaris');
});

it('shows create customer vehicle page with current branch customers', function (): void {
    $fixture = authenticateVehicleUser();

    $response = $this->get(vehiclesTenantRoute('customer-vehicles.create'));

    $response->assertSuccessful();

    $customerIds = $response->viewData('customers')->pluck('id')->all();
    expect($customerIds)->toContain($fixture['currentCustomer']->id);
    expect($customerIds)->not->toContain($fixture['secondaryCustomer']->id);
});

it('stores customer vehicle for current branch customer', function (): void {
    $fixture = authenticateVehicleUser();

    $response = $this->post(vehiclesTenantRoute('customer-vehicles.store'), [
        'customer_id' => $fixture['currentCustomer']->id,
        'registration_no' => 'REG-555',
        'model' => 'Hilux',
        'year' => 2020,
        'chassis_no' => 'CH-555',
        'engine_no' => 'EN-555',
        'meter_reading' => 1200,
    ]);

    $response->assertRedirect(vehiclesTenantRoute('customer-vehicles.index'));
    $response->assertSessionHas('status', 'Created.');

    $this->assertDatabaseHas('customer_vehicles', [
        'customer_id' => $fixture['currentCustomer']->id,
        'registration_no' => 'REG-555',
        'model' => 'Hilux',
    ], 'tenant');
});

it('validates required customer vehicle fields', function (string $field): void {
    $fixture = authenticateVehicleUser();

    $payload = [
        'customer_id' => $fixture['currentCustomer']->id,
        'registration_no' => 'REQ-123',
    ];

    unset($payload[$field]);

    $response = $this->from(vehiclesTenantRoute('customer-vehicles.create'))
        ->post(vehiclesTenantRoute('customer-vehicles.store'), $payload);

    $response->assertRedirect(vehiclesTenantRoute('customer-vehicles.create'));
    $response->assertSessionHasErrors([$field]);
})->with([
    'customer_id' => 'customer_id',
    'registration_no' => 'registration_no',
]);

it('validates customer belongs to current branch', function (): void {
    $fixture = authenticateVehicleUser();

    $response = $this->from(vehiclesTenantRoute('customer-vehicles.create'))
        ->post(vehiclesTenantRoute('customer-vehicles.store'), [
            'customer_id' => $fixture['secondaryCustomer']->id,
            'registration_no' => 'NOPE-999',
        ]);

    $response->assertRedirect(vehiclesTenantRoute('customer-vehicles.create'));
    $response->assertSessionHasErrors(['customer_id']);
});

it('validates unique registration and numeric ranges', function (): void {
    $fixture = authenticateVehicleUser();

    CustomerVehicle::query()->create([
        'customer_id' => $fixture['currentCustomer']->id,
        'registration_no' => 'DUP-001',
        'model' => 'City',
    ]);

    $response = $this->from(vehiclesTenantRoute('customer-vehicles.create'))
        ->post(vehiclesTenantRoute('customer-vehicles.store'), [
            'customer_id' => $fixture['currentCustomer']->id,
            'registration_no' => 'DUP-001',
            'year' => 1900,
            'meter_reading' => -1,
        ]);

    $response->assertRedirect(vehiclesTenantRoute('customer-vehicles.create'));
    $response->assertSessionHasErrors(['registration_no', 'year', 'meter_reading']);
});

it('validates customer id uuid format', function (): void {
    authenticateVehicleUser();

    $response = $this->from(vehiclesTenantRoute('customer-vehicles.create'))
        ->post(vehiclesTenantRoute('customer-vehicles.store'), [
            'customer_id' => 'not-a-uuid',
            'registration_no' => 'UUID-111',
        ]);

    $response->assertRedirect(vehiclesTenantRoute('customer-vehicles.create'));
    $response->assertSessionHasErrors(['customer_id']);
});

it('clamps customer vehicles pagination limits', function (): void {
    authenticateVehicleUser();

    $minResponse = $this->get(vehiclesTenantRoute('customer-vehicles.index', ['per_page' => 1]));
    $maxResponse = $this->get(vehiclesTenantRoute('customer-vehicles.index', ['per_page' => 999]));

    expect($minResponse->viewData('items')->perPage())->toBe(5);
    expect($maxResponse->viewData('items')->perPage())->toBe(100);
});

it('shows customer vehicle details for current branch', function (): void {
    $fixture = authenticateVehicleUser();

    $vehicle = CustomerVehicle::query()->create([
        'customer_id' => $fixture['currentCustomer']->id,
        'registration_no' => 'SHOW-111',
        'model' => 'Show Model',
    ]);

    session()->put('tenant.current_branch_id', $fixture['current']->id);
    $response = (new CustomerVehicleController())->show($vehicle, new EnsureVehicleInBranchAction());

    expect($response->name())->toBe('tenants.customer-vehicles.show');
    expect($response->getData()['vehicle']->id)->toBe($vehicle->id);
});

it('shows edit customer vehicle page for current branch', function (): void {
    $fixture = authenticateVehicleUser();

    $vehicle = CustomerVehicle::query()->create([
        'customer_id' => $fixture['currentCustomer']->id,
        'registration_no' => 'EDIT-111',
        'model' => 'Edit Model',
    ]);

    session()->put('tenant.current_branch_id', $fixture['current']->id);
    $response = (new CustomerVehicleController())->edit($vehicle, new EnsureVehicleInBranchAction());

    expect($response->name())->toBe('tenants.customer-vehicles.edit');
    expect($response->getData()['vehicle']->id)->toBe($vehicle->id);
});

it('deletes customer vehicle in current branch', function (): void {
    $fixture = authenticateVehicleUser();

    $vehicle = CustomerVehicle::query()->create([
        'customer_id' => $fixture['currentCustomer']->id,
        'registration_no' => 'DEL-111',
        'model' => 'Delete Model',
    ]);

    session()->put('tenant.current_branch_id', $fixture['current']->id);
    $response = (new CustomerVehicleController())->destroy(
        $vehicle,
        new DeleteCustomerVehicleAction(),
        new EnsureVehicleInBranchAction()
    );

    expect($response->getTargetUrl())->toBe(vehiclesTenantRoute('customer-vehicles.index'));
    expect($response->getSession()->get('status'))->toBe('Deleted.');
    $this->assertDatabaseMissing('customer_vehicles', ['id' => $vehicle->id], 'tenant');
});

it('throws not found when showing customer vehicle outside current branch', function (): void {
    $fixture = authenticateVehicleUser();

    $foreignVehicle = CustomerVehicle::query()->create([
        'customer_id' => $fixture['secondaryCustomer']->id,
        'registration_no' => 'ALT-404',
        'model' => 'Foreign Model',
    ]);

    $this->expectException(NotFoundHttpException::class);
    (new CustomerVehicleController())->show($foreignVehicle, new EnsureVehicleInBranchAction());
});

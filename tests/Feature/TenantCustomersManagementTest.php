<?php

declare(strict_types=1);

use App\Actions\Tenant\Customer\DeleteCustomerAction;
use App\Enums\BranchStatus;
use App\Enums\CustomerStatus;
use App\Http\Controllers\Tenant\CustomerController;
use App\Models\Branch;
use App\Models\Customer;
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

function customersTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

/**
 * @return array{current: Branch, secondary: Branch}
 */
function authenticateCustomerUser(): array
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
        'name' => 'Customer User',
        'email' => 'customer.user+'.uniqid('', true).'@example.test',
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

it('shows customers index', function (): void {
    $branches = authenticateCustomerUser();

    Customer::query()->withoutGlobalScopes()->create([
        'branch_id' => $branches['current']->id,
        'code' => 'CUST-001',
        'name' => 'Ali Motors',
        'status' => CustomerStatus::ACTIVE->value,
    ]);

    $response = $this->get(customersTenantRoute('customers.index'));

    $response->assertSuccessful();
    $response->assertSee('Customers');
    $response->assertSee('Ali Motors');
});

it('shows create customer page', function (): void {
    authenticateCustomerUser();

    $response = $this->get(customersTenantRoute('customers.create'));

    $response->assertSuccessful();
    $response->assertSee('Create Customer');
});

it('stores customer for current branch', function (): void {
    $branches = authenticateCustomerUser();

    $response = $this->post(customersTenantRoute('customers.store'), [
        'code' => 'CUST-NEW',
        'name' => 'New Customer',
        'phone' => '03001234567',
        'email' => 'new.customer@example.test',
        'credit_limit' => 5000,
        'opening_balance' => 100,
        'status' => CustomerStatus::ACTIVE->value,
    ]);

    $response->assertRedirect(customersTenantRoute('customers.index'));
    $response->assertSessionHas('status', 'Created.');

    $this->assertDatabaseHas('customers', [
        'branch_id' => $branches['current']->id,
        'code' => 'CUST-NEW',
        'name' => 'New Customer',
        'status' => CustomerStatus::ACTIVE->value,
    ], 'tenant');
});

it('validates required customer fields', function (string $field): void {
    authenticateCustomerUser();

    $payload = [
        'code' => 'CUST-REQ',
        'name' => 'Required Customer',
        'status' => CustomerStatus::ACTIVE->value,
    ];

    unset($payload[$field]);

    $response = $this->from(customersTenantRoute('customers.create'))
        ->post(customersTenantRoute('customers.store'), $payload);

    $response->assertRedirect(customersTenantRoute('customers.create'));
    $response->assertSessionHasErrors([$field]);
})->with([
    'code' => 'code',
    'name' => 'name',
    'status' => 'status',
]);

it('validates customer code uniqueness in current branch', function (): void {
    $branches = authenticateCustomerUser();

    Customer::query()->withoutGlobalScopes()->create([
        'branch_id' => $branches['current']->id,
        'code' => 'CUST-DUP',
        'name' => 'Existing Customer',
        'status' => CustomerStatus::ACTIVE->value,
    ]);

    $response = $this->from(customersTenantRoute('customers.create'))
        ->post(customersTenantRoute('customers.store'), [
            'code' => 'CUST-DUP',
            'name' => 'Duplicate Customer',
            'status' => CustomerStatus::ACTIVE->value,
        ]);

    $response->assertRedirect(customersTenantRoute('customers.create'));
    $response->assertSessionHasErrors(['code' => 'This customer code already exists for the selected branch.']);
});

it('allows same customer code in different branch', function (): void {
    $branches = authenticateCustomerUser();

    Customer::query()->withoutGlobalScopes()->create([
        'branch_id' => $branches['secondary']->id,
        'code' => 'CUST-SHARED',
        'name' => 'Other Branch Customer',
        'status' => CustomerStatus::ACTIVE->value,
    ]);

    $response = $this->post(customersTenantRoute('customers.store'), [
        'code' => 'CUST-SHARED',
        'name' => 'Current Branch Customer',
        'status' => CustomerStatus::ACTIVE->value,
    ]);

    $response->assertRedirect(customersTenantRoute('customers.index'));

    $count = Customer::query()->withoutGlobalScopes()->where('code', 'CUST-SHARED')->count();
    expect($count)->toBe(2);
});

it('validates customer email format and credit limit minimum', function (): void {
    authenticateCustomerUser();

    $response = $this->from(customersTenantRoute('customers.create'))
        ->post(customersTenantRoute('customers.store'), [
            'code' => 'CUST-VAL',
            'name' => 'Validation Customer',
            'email' => 'not-an-email',
            'credit_limit' => -1,
            'status' => CustomerStatus::ACTIVE->value,
        ]);

    $response->assertRedirect(customersTenantRoute('customers.create'));
    $response->assertSessionHasErrors(['email', 'credit_limit']);
});

it('clamps customers pagination limits', function (): void {
    authenticateCustomerUser();

    $minResponse = $this->get(customersTenantRoute('customers.index', ['per_page' => 1]));
    $maxResponse = $this->get(customersTenantRoute('customers.index', ['per_page' => 999]));

    expect($minResponse->viewData('items')->perPage())->toBe(5);
    expect($maxResponse->viewData('items')->perPage())->toBe(100);
});

it('shows customer details in current branch', function (): void {
    $branches = authenticateCustomerUser();

    $customer = Customer::query()->withoutGlobalScopes()->create([
        'branch_id' => $branches['current']->id,
        'code' => 'CUST-SHOW',
        'name' => 'Show Customer',
        'status' => CustomerStatus::ACTIVE->value,
    ]);

    session()->put('tenant.current_branch_id', $branches['current']->id);
    $response = (new CustomerController())->show($customer);

    expect($response->name())->toBe('tenants.customers.show');
    expect($response->getData()['customer']->id)->toBe($customer->id);
});

it('shows edit customer page for current branch customer', function (): void {
    $branches = authenticateCustomerUser();

    $customer = Customer::query()->withoutGlobalScopes()->create([
        'branch_id' => $branches['current']->id,
        'code' => 'CUST-EDIT',
        'name' => 'Edit Customer',
        'status' => CustomerStatus::ACTIVE->value,
    ]);

    session()->put('tenant.current_branch_id', $branches['current']->id);
    $response = (new CustomerController())->edit($customer);

    expect($response->name())->toBe('tenants.customers.edit');
    expect($response->getData()['customer']->id)->toBe($customer->id);
});

it('validates customer status enum values', function (): void {
    authenticateCustomerUser();

    $response = $this->from(customersTenantRoute('customers.create'))
        ->post(customersTenantRoute('customers.store'), [
            'code' => 'CUST-BAD-STATUS',
            'name' => 'Bad Status Customer',
            'status' => 'not-valid',
        ]);

    $response->assertRedirect(customersTenantRoute('customers.create'));
    $response->assertSessionHasErrors(['status']);
});

it('deletes customer', function (): void {
    $branches = authenticateCustomerUser();

    $customer = Customer::query()->withoutGlobalScopes()->create([
        'branch_id' => $branches['current']->id,
        'code' => 'CUST-DEL',
        'name' => 'Delete Customer',
        'status' => CustomerStatus::ACTIVE->value,
    ]);

    session()->put('tenant.current_branch_id', $branches['current']->id);
    $response = (new CustomerController())->destroy($customer, new DeleteCustomerAction());

    expect($response->getTargetUrl())->toBe(customersTenantRoute('customers.index'));
    expect($response->getSession()->get('status'))->toBe('Deleted.');
    $this->assertSoftDeleted('customers', ['id' => $customer->id], 'tenant');
});

it('throws not found when showing customer outside current branch', function (): void {
    $branches = authenticateCustomerUser();

    $foreignCustomer = Customer::query()->withoutGlobalScopes()->create([
        'branch_id' => $branches['secondary']->id,
        'code' => 'CUST-ALT-404',
        'name' => 'Foreign Customer',
        'status' => CustomerStatus::ACTIVE->value,
    ]);

    $this->expectException(NotFoundHttpException::class);
    (new CustomerController())->show($foreignCustomer);
});

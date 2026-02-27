<?php

declare(strict_types=1);

use App\Enums\BranchStatus;
use App\Enums\UserStatus;
use App\Models\Branch;
use App\Models\EmployeeSalary;
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

function salaryTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

/**
 * @return array{branch: Branch, user: User, employee: User}
 */
function authenticateSalaryUser(): array
{
    $branch = Branch::query()->create([
        'code' => 'MAIN',
        'name' => 'Main Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $user = User::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Salary Admin',
        'email' => 'salary.admin+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    $employee = User::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Salary Employee',
        'email' => 'salary.employee+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $branch->id]);

    return ['branch' => $branch, 'user' => $user, 'employee' => $employee];
}

it('shows salary index and summary', function (): void {
    $fixture = authenticateSalaryUser();

    EmployeeSalary::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['branch']->id,
        'user_id' => $fixture['employee']->id,
        'salary_month' => now()->startOfMonth()->toDateString(),
        'per_day_salary' => 100,
        'working_days' => 10,
        'basic_salary' => 1000,
        'bonus' => 100,
        'deduction' => 50,
        'net_salary' => 1050,
        'paid_at' => now(),
    ]);

    $response = $this->get(salaryTenantRoute('employee-salaries.index'));

    $response->assertSuccessful();
    $response->assertSee('data-ajax-table-search', false);
    $response->assertSee('data-ajax-sort-link', false);
    $response->assertSee('id="employee-salaries-search-form"', false);

    $summary = $response->viewData('summary');

    expect($summary['employees_count'])->toBe(2);
    expect($summary['configured_count'])->toBe(1);
    expect($summary['paid_total'])->toBe(1050.0);
});

it('sorts salary employees by name ascending and descending', function (): void {
    $fixture = authenticateSalaryUser();

    User::query()->create([
        'branch_id' => $fixture['branch']->id,
        'name' => 'Salary Sort A',
        'email' => 'salary.sort.a+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    User::query()->create([
        'branch_id' => $fixture['branch']->id,
        'name' => 'Salary Sort Z',
        'email' => 'salary.sort.z+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    $ascending = $this->get(salaryTenantRoute('employee-salaries.index', [
        'sort_by' => 'name',
        'sort_direction' => 'asc',
    ]));

    $descending = $this->get(salaryTenantRoute('employee-salaries.index', [
        'sort_by' => 'name',
        'sort_direction' => 'desc',
    ]));

    $ascendingNames = $ascending->viewData('employees')->pluck('name')->values()->all();
    $descendingNames = $descending->viewData('employees')->pluck('name')->values()->all();

    expect(array_search('Salary Sort A', $ascendingNames, true))->toBeLessThan(array_search('Salary Sort Z', $ascendingNames, true));
    expect(array_search('Salary Sort A', $descendingNames, true))->toBeGreaterThan(array_search('Salary Sort Z', $descendingNames, true));
});

it('searches salary employees by name', function (): void {
    authenticateSalaryUser();

    $response = $this->get(salaryTenantRoute('employee-salaries.index', ['search' => 'Salary Employee']));

    $response->assertSuccessful();

    expect($response->viewData('employees')->count())->toBe(1);
});

it('stores salary record with computed net salary', function (): void {
    $fixture = authenticateSalaryUser();

    $response = $this->post(salaryTenantRoute('employee-salaries.store'), [
        'user_id' => $fixture['employee']->id,
        'salary_month' => now()->format('Y-m'),
        'per_day_salary' => 100,
        'working_days' => 10,
        'bonus' => 100,
        'deduction' => 200,
        'action' => 'save',
    ]);

    $response->assertRedirect();

    $record = EmployeeSalary::query()->firstOrFail();
    expect((float) $record->basic_salary)->toBe(1000.0);
    expect((float) $record->net_salary)->toBe(900.0);
    expect($record->paid_at)->toBeNull();
});

it('marks salary record as paid when action is mark_paid', function (): void {
    $fixture = authenticateSalaryUser();

    $response = $this->post(salaryTenantRoute('employee-salaries.store'), [
        'user_id' => $fixture['employee']->id,
        'salary_month' => now()->format('Y-m'),
        'per_day_salary' => 100,
        'working_days' => 10,
        'bonus' => 0,
        'deduction' => 0,
        'action' => 'mark_paid',
    ]);

    $response->assertRedirect();

    $record = EmployeeSalary::query()->firstOrFail();
    expect($record->paid_at)->not->toBeNull();
});

it('validates salary payload', function (): void {
    authenticateSalaryUser();

    $response = $this->from(salaryTenantRoute('employee-salaries.index'))
        ->post(salaryTenantRoute('employee-salaries.store'), [
            'salary_month' => 'bad-month',
            'per_day_salary' => -1,
            'working_days' => 35,
            'action' => 'invalid',
        ]);

    $response->assertRedirect(salaryTenantRoute('employee-salaries.index'));
    $response->assertSessionHasErrors(['user_id', 'salary_month', 'per_day_salary', 'working_days', 'action']);
});

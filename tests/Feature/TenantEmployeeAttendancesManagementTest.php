<?php

declare(strict_types=1);

use App\Enums\BranchStatus;
use App\Enums\UserStatus;
use App\Models\Branch;
use App\Models\EmployeeAttendance;
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

function attendanceTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

/**
 * @return array{branch: Branch, user: User, employee: User}
 */
function authenticateAttendanceUser(): array
{
    $branch = Branch::query()->create([
        'code' => 'MAIN',
        'name' => 'Main Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $user = User::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Attendance Admin',
        'email' => 'attendance.admin+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    $employee = User::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Employee One',
        'email' => 'employee.one+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $branch->id]);

    return ['branch' => $branch, 'user' => $user, 'employee' => $employee];
}

it('shows attendance index and summary', function (): void {
    $fixture = authenticateAttendanceUser();

    EmployeeAttendance::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['branch']->id,
        'user_id' => $fixture['employee']->id,
        'attendance_date' => now()->toDateString(),
        'check_in_at' => now()->subHours(2),
        'check_out_at' => now()->subHour(),
        'total_minutes' => 60,
    ]);

    $response = $this->get(attendanceTenantRoute('employee-attendances.index'));

    $response->assertSuccessful();
    $response->assertSee('data-ajax-table-search', false);
    $response->assertSee('data-ajax-sort-link', false);
    $response->assertSee('id="employee-attendances-search-form"', false);

    $summary = $response->viewData('summary');

    expect($summary['employees_count'])->toBe(2);
    expect($summary['checked_in_count'])->toBe(1);
    expect($summary['checked_out_count'])->toBe(1);
});

it('sorts attendance employees by name ascending and descending', function (): void {
    $fixture = authenticateAttendanceUser();

    User::query()->create([
        'branch_id' => $fixture['branch']->id,
        'name' => 'Attendance Sort A',
        'email' => 'attendance.sort.a+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    User::query()->create([
        'branch_id' => $fixture['branch']->id,
        'name' => 'Attendance Sort Z',
        'email' => 'attendance.sort.z+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    $ascending = $this->get(attendanceTenantRoute('employee-attendances.index', [
        'sort_by' => 'name',
        'sort_direction' => 'asc',
    ]));

    $descending = $this->get(attendanceTenantRoute('employee-attendances.index', [
        'sort_by' => 'name',
        'sort_direction' => 'desc',
    ]));

    $ascendingNames = $ascending->viewData('employees')->pluck('name')->values()->all();
    $descendingNames = $descending->viewData('employees')->pluck('name')->values()->all();

    expect(array_search('Attendance Sort A', $ascendingNames, true))->toBeLessThan(array_search('Attendance Sort Z', $ascendingNames, true));
    expect(array_search('Attendance Sort A', $descendingNames, true))->toBeGreaterThan(array_search('Attendance Sort Z', $descendingNames, true));
});

it('searches attendance employees by name', function (): void {
    authenticateAttendanceUser();

    $response = $this->get(attendanceTenantRoute('employee-attendances.index', ['search' => 'Employee One']));

    $response->assertSuccessful();

    expect($response->viewData('employees')->count())->toBe(1);
});

it('stores check in attendance action', function (): void {
    $fixture = authenticateAttendanceUser();

    $response = $this->post(attendanceTenantRoute('employee-attendances.store'), [
        'user_id' => $fixture['employee']->id,
        'attendance_date' => now()->toDateString(),
        'action' => 'check_in',
    ]);

    $response->assertRedirect();

    $record = EmployeeAttendance::query()->firstOrFail();
    expect($record->check_in_at)->not->toBeNull();
});

it('stores check out attendance action and computes total minutes', function (): void {
    $fixture = authenticateAttendanceUser();
    $attendanceDate = today()->toDateTimeString();

    $record = EmployeeAttendance::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['branch']->id,
        'user_id' => $fixture['employee']->id,
        'attendance_date' => $attendanceDate,
        'check_in_at' => now()->subHours(2),
    ]);

    $response = $this->post(attendanceTenantRoute('employee-attendances.store'), [
        'user_id' => $fixture['employee']->id,
        'attendance_date' => $attendanceDate,
        'action' => 'check_out',
    ]);

    $response->assertRedirect();

    $record->refresh();
    expect($record->check_out_at)->not->toBeNull();
    expect($record->total_minutes)->toBeGreaterThan(0);
});

it('marks employee absent and clears check in and check out times', function (): void {
    $fixture = authenticateAttendanceUser();
    $attendanceDate = today()->toDateTimeString();

    $record = EmployeeAttendance::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['branch']->id,
        'user_id' => $fixture['employee']->id,
        'attendance_date' => $attendanceDate,
        'check_in_at' => now()->subHours(2),
        'check_out_at' => now()->subHour(),
        'total_minutes' => 60,
    ]);

    $response = $this->post(attendanceTenantRoute('employee-attendances.store'), [
        'user_id' => $fixture['employee']->id,
        'attendance_date' => $attendanceDate,
        'action' => 'mark_absent',
    ]);

    $response->assertRedirect();

    $record->refresh();
    expect($record->check_in_at)->toBeNull();
    expect($record->check_out_at)->toBeNull();
    expect($record->total_minutes)->toBeNull();
});

it('validates attendance payload', function (): void {
    authenticateAttendanceUser();

    $response = $this->from(attendanceTenantRoute('employee-attendances.index'))
        ->post(attendanceTenantRoute('employee-attendances.store'), [
            'attendance_date' => 'bad-date',
            'action' => 'invalid',
        ]);

    $response->assertRedirect(attendanceTenantRoute('employee-attendances.index'));
    $response->assertSessionHasErrors(['user_id', 'attendance_date', 'action']);
});

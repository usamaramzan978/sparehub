<?php

declare(strict_types=1);

use App\Actions\Tenant\Unit\DeleteUnitAction;
use App\Actions\Tenant\Unit\UpdateUnitAction;
use App\Enums\BranchStatus;
use App\Enums\RecordStatus;
use App\Models\Branch;
use App\Models\Unit;
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

function unitsTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

function authenticateUnitUser(): void
{
    $branch = Branch::query()->create([
        'code' => 'MAIN',
        'name' => 'Main Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $user = User::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Unit User',
        'email' => 'unit.user+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => 'active',
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $branch->id]);
}

it('shows units index', function (): void {
    authenticateUnitUser();

    Unit::query()->create([
        'code' => 'PCS',
        'name' => 'Pieces',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $response = $this->get(unitsTenantRoute('units.index'));

    $response->assertSuccessful();
    $response->assertSee('Units');
    $response->assertSee('Pieces');
    $response->assertSee('data-ajax-table-search', false);
    $response->assertSee('data-ajax-sort-link', false);
    $response->assertSee('units-search-form');
    $response->assertSee('units-search-loading');
});

it('filters units by code or name', function (): void {
    authenticateUnitUser();

    Unit::query()->create([
        'code' => 'PCS',
        'name' => 'Pieces',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    Unit::query()->create([
        'code' => 'LTR',
        'name' => 'Liter',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $byCodeResponse = $this->get(unitsTenantRoute('units.index', ['search' => 'PCS']));
    $byNameResponse = $this->get(unitsTenantRoute('units.index', ['search' => 'Liter']));

    $byCodeResponse->assertSuccessful();
    $byCodeResponse->assertSee('Pieces');
    $byCodeResponse->assertDontSee('Liter');

    $byNameResponse->assertSuccessful();
    $byNameResponse->assertSee('Liter');
    $byNameResponse->assertDontSee('Pieces');
});

it('sorts units by name ascending and descending', function (): void {
    authenticateUnitUser();

    Unit::query()->create([
        'code' => 'UNI-A',
        'name' => 'AAA Unit',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    Unit::query()->create([
        'code' => 'UNI-Z',
        'name' => 'ZZZ Unit',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $ascending = $this->get(unitsTenantRoute('units.index', [
        'sort_by' => 'name',
        'sort_direction' => 'asc',
    ]));

    $descending = $this->get(unitsTenantRoute('units.index', [
        'sort_by' => 'name',
        'sort_direction' => 'desc',
    ]));

    expect($ascending->viewData('items')->pluck('name')->values()->all())->toBe([
        'AAA Unit',
        'ZZZ Unit',
    ]);
    expect($descending->viewData('items')->pluck('name')->values()->all())->toBe([
        'ZZZ Unit',
        'AAA Unit',
    ]);
});

it('stores unit with fractional flag', function (): void {
    authenticateUnitUser();

    $response = $this->post(unitsTenantRoute('units.store'), [
        'code' => 'KG',
        'name' => 'Kilogram',
        'is_fractional' => '1',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $response->assertRedirect(unitsTenantRoute('units.index'));
    $response->assertSessionHas('status', 'Created.');

    $this->assertDatabaseHas('units', [
        'code' => 'KG',
        'name' => 'Kilogram',
        'is_fractional' => 1,
        'status' => RecordStatus::ACTIVE->value,
    ], 'tenant');
});

it('stores unit with false fractional flag when omitted', function (): void {
    authenticateUnitUser();

    $this->post(unitsTenantRoute('units.store'), [
        'code' => 'BOX',
        'name' => 'Box',
        'status' => RecordStatus::ACTIVE->value,
    ])->assertRedirect(unitsTenantRoute('units.index'));

    $this->assertDatabaseHas('units', [
        'code' => 'BOX',
        'is_fractional' => 0,
    ], 'tenant');
});

it('validates required unit fields', function (string $field): void {
    authenticateUnitUser();

    $payload = [
        'code' => 'MTR',
        'name' => 'Meter',
        'status' => RecordStatus::ACTIVE->value,
    ];

    unset($payload[$field]);

    $response = $this->from(unitsTenantRoute('units.index'))
        ->post(unitsTenantRoute('units.store'), $payload);

    $response->assertRedirect(unitsTenantRoute('units.index'));
    $response->assertSessionHasErrors([$field]);
})->with([
    'code' => 'code',
    'name' => 'name',
    'status' => 'status',
]);

it('validates unique unit code', function (): void {
    authenticateUnitUser();

    Unit::query()->create([
        'code' => 'LTR',
        'name' => 'Liter',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $response = $this->from(unitsTenantRoute('units.index'))
        ->post(unitsTenantRoute('units.store'), [
            'code' => 'LTR',
            'name' => 'Liter Duplicate',
            'status' => RecordStatus::ACTIVE->value,
        ]);

    $response->assertRedirect(unitsTenantRoute('units.index'));
    $response->assertSessionHasErrors(['code' => 'This unit code already exists.']);
});

it('clamps units pagination limits', function (): void {
    authenticateUnitUser();

    $minResponse = $this->get(unitsTenantRoute('units.index', ['per_page' => 1]));
    $maxResponse = $this->get(unitsTenantRoute('units.index', ['per_page' => 999]));

    expect($minResponse->viewData('items')->perPage())->toBe(5);
    expect($maxResponse->viewData('items')->perPage())->toBe(100);
});

it('updates unit via action', function (): void {
    authenticateUnitUser();

    $unit = Unit::query()->create([
        'code' => 'SET',
        'name' => 'Set',
        'is_fractional' => false,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $updated = (new UpdateUnitAction())->handle($unit, [
        'code' => 'SET2',
        'name' => 'Set 2',
        'is_fractional' => true,
        'status' => RecordStatus::INACTIVE->value,
    ]);

    expect($updated)->toBeTrue();

    $this->assertDatabaseHas('units', [
        'id' => $unit->id,
        'code' => 'SET2',
        'name' => 'Set 2',
        'is_fractional' => 1,
        'status' => RecordStatus::INACTIVE->value,
    ], 'tenant');
});

it('deletes unit via action', function (): void {
    authenticateUnitUser();

    $unit = Unit::query()->create([
        'code' => 'DEL',
        'name' => 'Delete Unit',
        'is_fractional' => false,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $deleted = (new DeleteUnitAction())->handle($unit);

    expect($deleted)->toBeTrue();
    $this->assertDatabaseMissing('units', ['id' => $unit->id], 'tenant');
});

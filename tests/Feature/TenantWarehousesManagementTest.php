<?php

declare(strict_types=1);

use App\Actions\Tenant\Warehouse\DeleteWarehouseAction;
use App\Actions\Tenant\Warehouse\UpdateWarehouseAction;
use App\Enums\BranchStatus;
use App\Enums\RecordStatus;
use App\Http\Controllers\Tenant\WarehouseController;
use App\Models\Branch;
use App\Models\User;
use App\Models\Warehouse;
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

function warehouseTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

/**
 * @return array{branch: Branch, user: User}
 */
function createAuthenticatedWarehouseUser(): array
{
    $branch = Branch::query()->create([
        'code' => 'MAIN',
        'name' => 'Main Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $user = User::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Warehouse User',
        'email' => 'warehouse.user+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => 'active',
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $branch->id]);

    return [
        'branch' => $branch,
        'user' => $user,
    ];
}

it('shows warehouse index with warehouses from available branches', function (): void {
    $fixture = createAuthenticatedWarehouseUser();

    Warehouse::query()->create([
        'branch_id' => $fixture['branch']->id,
        'code' => 'WH-MAIN',
        'name' => 'Main Branch Warehouse',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $otherBranch = Branch::query()->create([
        'code' => 'BR-OTHER',
        'name' => 'Other Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    Warehouse::query()->withoutGlobalScopes()->create([
        'branch_id' => $otherBranch->id,
        'code' => 'WH-OTHER',
        'name' => 'Other Branch Warehouse',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $response = $this->get(warehouseTenantRoute('warehouses.index'));

    $response->assertSuccessful();
    $response->assertSee('Main Branch Warehouse');
    $response->assertSee('Other Branch Warehouse');
    $response->assertSee('data-ajax-table-search', false);
    $response->assertSee('warehouses-search-form');
    $response->assertSee('warehouses-search-loading');
});

it('filters warehouses by name or code', function (): void {
    $fixture = createAuthenticatedWarehouseUser();

    Warehouse::query()->create([
        'branch_id' => $fixture['branch']->id,
        'code' => 'WH-AX1',
        'name' => 'Axle Storage',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    Warehouse::query()->create([
        'branch_id' => $fixture['branch']->id,
        'code' => 'WH-BRK',
        'name' => 'Brake Room',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $byCodeResponse = $this->get(warehouseTenantRoute('warehouses.index', ['search' => 'AX1']));
    $byNameResponse = $this->get(warehouseTenantRoute('warehouses.index', ['search' => 'Brake']));

    $byCodeResponse->assertSee('Axle Storage');
    $byCodeResponse->assertDontSee('Brake Room');

    $byNameResponse->assertSee('Brake Room');
    $byNameResponse->assertDontSee('Axle Storage');
});

it('validates required warehouse fields', function (string $field): void {
    createAuthenticatedWarehouseUser();

    $payload = [
        'code' => 'WH-REQ',
        'name' => 'Required Warehouse',
        'status' => RecordStatus::ACTIVE->value,
    ];

    unset($payload[$field]);

    $response = $this->from(warehouseTenantRoute('warehouses.index'))
        ->post(warehouseTenantRoute('warehouses.store'), $payload);

    $response->assertRedirect(warehouseTenantRoute('warehouses.index'));
    $response->assertSessionHasErrors([$field]);
})->with([
    'code' => 'code',
    'name' => 'name',
    'status' => 'status',
]);

it('uses custom validation messages for warehouse fields', function (): void {
    createAuthenticatedWarehouseUser();

    $response = $this->from(warehouseTenantRoute('warehouses.index'))
        ->post(warehouseTenantRoute('warehouses.store'), [
            'code' => '',
            'name' => '',
            'status' => '',
        ]);

    $response->assertRedirect(warehouseTenantRoute('warehouses.index'));
    $response->assertSessionHasErrors([
        'code' => 'Warehouse code is required.',
        'name' => 'Warehouse name is required.',
        'status' => 'Please select a warehouse status.',
    ]);
});

it('rejects duplicate warehouse code inside same branch', function (): void {
    $fixture = createAuthenticatedWarehouseUser();

    Warehouse::query()->create([
        'branch_id' => $fixture['branch']->id,
        'code' => 'WH-DUP',
        'name' => 'Existing Warehouse',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $response = $this->from(warehouseTenantRoute('warehouses.index'))
        ->post(warehouseTenantRoute('warehouses.store'), [
            'code' => 'WH-DUP',
            'name' => 'Duplicate Warehouse',
            'status' => RecordStatus::ACTIVE->value,
        ]);

    $response->assertRedirect(warehouseTenantRoute('warehouses.index'));
    $response->assertSessionHasErrors(['code' => 'This warehouse code already exists for current branch.']);
});

it('deletes warehouse', function (): void {
    $fixture = createAuthenticatedWarehouseUser();

    $warehouse = Warehouse::query()->create([
        'branch_id' => $fixture['branch']->id,
        'code' => 'WH-DEL',
        'name' => 'Delete Warehouse',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    session()->put('tenant.current_branch_id', $fixture['branch']->id);

    $response = (new WarehouseController())->destroy($warehouse, new DeleteWarehouseAction());

    expect($response->getTargetUrl())->toBe(warehouseTenantRoute('warehouses.index'));
    expect($response->getSession()->get('status'))->toBe('Deleted.');

    $this->assertDatabaseMissing('warehouses', ['id' => $warehouse->id], 'tenant');
});

it('updates warehouse via action', function (): void {
    $fixture = createAuthenticatedWarehouseUser();

    $warehouse = Warehouse::query()->create([
        'branch_id' => $fixture['branch']->id,
        'code' => 'WH-UPD',
        'name' => 'Before Update',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $updated = (new UpdateWarehouseAction())->handle($warehouse, [
        'branch_id' => $fixture['branch']->id,
        'code' => 'WH-UPD',
        'name' => 'After Update',
        'status' => RecordStatus::INACTIVE->value,
    ]);

    expect($updated)->toBeTrue();
    $this->assertDatabaseHas('warehouses', [
        'id' => $warehouse->id,
        'name' => 'After Update',
        'status' => RecordStatus::INACTIVE->value,
    ], 'tenant');
});

it('clamps warehouse pagination to minimum and maximum per-page limits', function (): void {
    createAuthenticatedWarehouseUser();

    $minResponse = $this->get(warehouseTenantRoute('warehouses.index', ['per_page' => 1]));
    $maxResponse = $this->get(warehouseTenantRoute('warehouses.index', ['per_page' => 999]));

    expect($minResponse->viewData('items')->perPage())->toBe(5);
    expect($maxResponse->viewData('items')->perPage())->toBe(100);
});

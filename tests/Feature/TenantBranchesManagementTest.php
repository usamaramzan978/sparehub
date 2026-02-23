<?php

declare(strict_types=1);

use App\Actions\Tenant\Branch\DeleteBranchAction;
use App\Actions\Tenant\Branch\UpdateBranchAction;
use App\Enums\BranchStatus;
use App\Enums\RecordStatus;
use App\Http\Controllers\Tenant\BranchController;
use App\Models\Branch;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
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

function tenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

/**
 * @return array{branch: Branch, user: User}
 */
function createAuthenticatedTenantUser(): array
{
    $branch = Branch::query()->create([
        'code' => 'MAIN',
        'name' => 'Main Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $user = User::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Tenant User',
        'email' => 'tenant.user+'.uniqid('', true).'@example.test',
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

it('shows branches index', function (): void {
    createAuthenticatedTenantUser();

    Branch::query()->create([
        'code' => 'BR-002',
        'name' => 'North Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $response = $this->get(tenantRoute('branches.index'));

    $response->assertSuccessful();
    $response->assertSee('Branches');
    $response->assertSee('North Branch');
});

it('shows branch create page with warehouse options', function (): void {
    createAuthenticatedTenantUser();

    $warehouse = Warehouse::query()->create([
        'code' => 'WH-001',
        'name' => 'Main Warehouse',
        'status' => RecordStatus::ACTIVE->value,
        'branch_id' => Branch::query()->value('id'),
    ]);

    $response = $this->get(tenantRoute('branches.create'));

    $response->assertSuccessful();
    $response->assertSee('Create Branch');
    $response->assertSee($warehouse->name);
});

it('stores a branch', function (): void {
    createAuthenticatedTenantUser();

    $warehouse = Warehouse::query()->create([
        'code' => 'WH-001',
        'name' => 'Main Warehouse',
        'status' => RecordStatus::ACTIVE->value,
        'branch_id' => Branch::query()->value('id'),
    ]);

    $response = $this->post(tenantRoute('branches.store'), [
        'code' => 'BR-NEW',
        'name' => 'New Branch',
        'warehouse_id' => $warehouse->id,
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $response->assertRedirect(tenantRoute('branches.index'));
    $response->assertSessionHas('status', 'Created.');

    $this->assertDatabaseHas('branches', [
        'code' => 'BR-NEW',
        'name' => 'New Branch',
        'warehouse_id' => $warehouse->id,
        'status' => BranchStatus::ACTIVE->value,
    ], 'tenant');

    $event = DB::connection('tenant')
        ->table('tenant_activity_timelines')
        ->where('event', 'branch_created')
        ->latest('id')
        ->first();

    expect($event)->not->toBeNull();
});

it('validates required fields when storing branch', function (string $field): void {
    createAuthenticatedTenantUser();

    $payload = [
        'code' => 'BR-REQ',
        'name' => 'Required Check Branch',
        'status' => BranchStatus::ACTIVE->value,
    ];

    unset($payload[$field]);

    $response = $this->from(tenantRoute('branches.create'))
        ->post(tenantRoute('branches.store'), $payload);

    $response->assertRedirect(tenantRoute('branches.create'));
    $response->assertSessionHasErrors([$field]);
})->with([
    'code' => 'code',
    'name' => 'name',
    'status' => 'status',
]);

it('rejects duplicate branch code when storing', function (): void {
    createAuthenticatedTenantUser();

    Branch::query()->create([
        'code' => 'DUP-001',
        'name' => 'Existing Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $response = $this->from(tenantRoute('branches.create'))
        ->post(tenantRoute('branches.store'), [
            'code' => 'DUP-001',
            'name' => 'Duplicate Branch',
            'status' => BranchStatus::ACTIVE->value,
        ]);

    $response->assertRedirect(tenantRoute('branches.create'));
    $response->assertSessionHasErrors(['code']);
});

it('prevents deleting the last remaining branch', function (): void {
    $fixture = createAuthenticatedTenantUser();

    session()->put('tenant.current_branch_id', $fixture['branch']->id);

    $response = (new BranchController())->destroy('test-tenant-id', $fixture['branch'], new DeleteBranchAction());

    expect($response->getTargetUrl())->toBe(tenantRoute('branches.index'));
    expect($response->getSession()->get('error'))->toBe('At least one branch must remain.');

    $this->assertDatabaseHas('branches', ['id' => $fixture['branch']->id], 'tenant');
});

it('prevents deleting the currently selected branch', function (): void {
    $fixture = createAuthenticatedTenantUser();

    $otherBranch = Branch::query()->create([
        'code' => 'BR-OTHER',
        'name' => 'Other Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    session()->put('tenant.current_branch_id', $fixture['branch']->id);

    $response = (new BranchController())->destroy('test-tenant-id', $fixture['branch'], new DeleteBranchAction());

    expect($response->getTargetUrl())->toBe(tenantRoute('branches.index'));
    expect($response->getSession()->get('error'))->toBe('You cannot delete the currently selected branch.');

    $this->assertDatabaseHas('branches', ['id' => $fixture['branch']->id], 'tenant');
    $this->assertDatabaseHas('branches', ['id' => $otherBranch->id], 'tenant');
});

it('deletes branch when it is not the selected branch and at least one branch remains', function (): void {
    $fixture = createAuthenticatedTenantUser();

    $deletableBranch = Branch::query()->create([
        'code' => 'BR-DEL',
        'name' => 'Deletable Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    session()->put('tenant.current_branch_id', $fixture['branch']->id);

    $response = (new BranchController())->destroy('test-tenant-id', $deletableBranch, new DeleteBranchAction());

    expect($response->getTargetUrl())->toBe(tenantRoute('branches.index'));
    expect($response->getSession()->get('status'))->toBe('Deleted.');

    $this->assertDatabaseMissing('branches', ['id' => $deletableBranch->id], 'tenant');
    $this->assertDatabaseHas('branches', ['id' => $fixture['branch']->id], 'tenant');

    $event = DB::connection('tenant')
        ->table('tenant_activity_timelines')
        ->where('event', 'branch_deleted')
        ->latest('id')
        ->first();

    expect($event)->not->toBeNull();
});

it('updates a branch via action', function (): void {
    $fixture = createAuthenticatedTenantUser();

    $branch = Branch::query()->create([
        'code' => 'BR-UPD',
        'name' => 'Before Update',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $warehouse = Warehouse::query()->create([
        'code' => 'WH-UPD',
        'name' => 'Update Warehouse',
        'status' => RecordStatus::ACTIVE->value,
        'branch_id' => $fixture['branch']->id,
    ]);

    $updated = (new UpdateBranchAction())->handle($branch, [
        'code' => 'BR-UPD',
        'name' => 'After Update',
        'warehouse_id' => $warehouse->id,
        'status' => BranchStatus::INACTIVE->value,
    ]);

    expect($updated)->toBeTrue();

    $this->assertDatabaseHas('branches', [
        'id' => $branch->id,
        'name' => 'After Update',
        'warehouse_id' => $warehouse->id,
        'status' => BranchStatus::INACTIVE->value,
    ], 'tenant');

    $event = DB::connection('tenant')
        ->table('tenant_activity_timelines')
        ->where('event', 'branch_updated')
        ->latest('id')
        ->first();

    expect($event)->not->toBeNull();
});

it('clamps branch pagination to minimum and maximum per-page limits', function (): void {
    createAuthenticatedTenantUser();

    $minResponse = $this->get(tenantRoute('branches.index', ['per_page' => 1]));
    $maxResponse = $this->get(tenantRoute('branches.index', ['per_page' => 999]));

    expect($minResponse->viewData('items')->perPage())->toBe(5);
    expect($maxResponse->viewData('items')->perPage())->toBe(100);
});

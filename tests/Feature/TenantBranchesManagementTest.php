<?php

declare(strict_types=1);

use App\Actions\Tenant\Branch\UpdateBranchAction;
use App\Enums\BranchStatus;
use App\Models\Branch;
use App\Models\User;
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
    $response->assertSee('data-ajax-table-search', false);
    $response->assertSee('data-ajax-sort-link', false);
    $response->assertSee('branches-search-form');
    $response->assertSee('branches-search-loading');
});

it('filters branches by code or name', function (): void {
    createAuthenticatedTenantUser();

    Branch::query()->create([
        'code' => 'BR-001',
        'name' => 'North Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    Branch::query()->create([
        'code' => 'BR-002',
        'name' => 'South Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $byCodeResponse = $this->get(tenantRoute('branches.index', ['search' => 'BR-001']));
    $byNameResponse = $this->get(tenantRoute('branches.index', ['search' => 'South']));

    $byCodeResponse->assertSuccessful();
    expect($byCodeResponse->viewData('items')->pluck('name')->values()->all())->toContain('North Branch');
    expect($byCodeResponse->viewData('items')->pluck('name')->values()->all())->not->toContain('South Branch');

    $byNameResponse->assertSuccessful();
    expect($byNameResponse->viewData('items')->pluck('name')->values()->all())->toContain('South Branch');
    expect($byNameResponse->viewData('items')->pluck('name')->values()->all())->not->toContain('North Branch');
});

it('sorts branches by name ascending and descending', function (): void {
    createAuthenticatedTenantUser();

    Branch::query()->create([
        'code' => 'SRT-A',
        'name' => 'AAA Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    Branch::query()->create([
        'code' => 'SRT-Z',
        'name' => 'ZZZ Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $ascending = $this->get(tenantRoute('branches.index', [
        'sort_by' => 'name',
        'sort_direction' => 'asc',
    ]));

    $descending = $this->get(tenantRoute('branches.index', [
        'sort_by' => 'name',
        'sort_direction' => 'desc',
    ]));

    $ascendingNames = $ascending->viewData('items')->pluck('name')->values()->all();
    $descendingNames = $descending->viewData('items')->pluck('name')->values()->all();

    expect(array_search('AAA Branch', $ascendingNames, true))->toBeLessThan(array_search('ZZZ Branch', $ascendingNames, true));
    expect(array_search('AAA Branch', $descendingNames, true))->toBeGreaterThan(array_search('ZZZ Branch', $descendingNames, true));
});

it('shows branch create page', function (): void {
    createAuthenticatedTenantUser();

    $response = $this->get(tenantRoute('branches.create'));

    $response->assertSuccessful();
    $response->assertSee('Create Branch');
});

it('stores a branch', function (): void {
    createAuthenticatedTenantUser();

    $response = $this->post(tenantRoute('branches.store'), [
        'code' => 'BR-NEW',
        'name' => 'New Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $response->assertRedirect(tenantRoute('branches.index'));
    $response->assertSessionHas('status', 'Created.');

    $this->assertDatabaseHas('branches', [
        'code' => 'BR-NEW',
        'name' => 'New Branch',
        'status' => BranchStatus::ACTIVE->value,
    ], 'tenant');

    $event = DB::connection('tenant')
        ->table('tenant_activity_timelines')
        ->where('event', 'branch_created')
        ->latest('id')
        ->first();

    expect($event)->not->toBeNull();
});

it('prevents creating more branches than tenant max limit', function (): void {
    createAuthenticatedTenantUser();

    Config::set('tenancy.limits.max_branches', 3);

    Branch::query()->create([
        'code' => 'BR-002',
        'name' => 'Second Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    Branch::query()->create([
        'code' => 'BR-003',
        'name' => 'Third Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $response = $this->from(tenantRoute('branches.create'))
        ->post(tenantRoute('branches.store'), [
            'code' => 'BR-004',
            'name' => 'Fourth Branch',
            'status' => BranchStatus::ACTIVE->value,
        ]);

    $response->assertRedirect(tenantRoute('branches.create'));
    $response->assertSessionHasErrors(['code']);

    expect(Branch::query()->count())->toBe(3);
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

    $response = $this->delete(tenantRoute('branches.destroy', ['branch' => $fixture['branch']->id]));

    $response->assertRedirect(tenantRoute('branches.index'));
    $response->assertSessionHas('error', 'At least one branch must remain.');

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

    $response = $this->delete(tenantRoute('branches.destroy', ['branch' => $fixture['branch']->id]));

    $response->assertRedirect(tenantRoute('branches.index'));
    $response->assertSessionHas('error', 'You cannot delete the currently selected branch.');

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

    $response = $this->delete(tenantRoute('branches.destroy', ['branch' => $deletableBranch->id]));

    $response->assertRedirect(tenantRoute('branches.index'));
    $response->assertSessionHas('status', 'Deleted.');

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

    $updated = (new UpdateBranchAction())->handle($branch, [
        'code' => 'BR-UPD',
        'name' => 'After Update',
        'status' => BranchStatus::INACTIVE->value,
    ]);

    expect($updated)->toBeTrue();

    $this->assertDatabaseHas('branches', [
        'id' => $branch->id,
        'name' => 'After Update',
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

it('deletes a branch via the destroy route', function (): void {
    $fixture = createAuthenticatedTenantUser();

    $deletableBranch = Branch::query()->create([
        'code' => 'BR-ROUTE',
        'name' => 'Route Deletable Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    session()->put('tenant.current_branch_id', $fixture['branch']->id);

    $response = $this->delete(tenantRoute('branches.destroy', ['branch' => $deletableBranch->id]));

    $response->assertRedirect(tenantRoute('branches.index'));
    $response->assertSessionHas('status', 'Deleted.');
    $this->assertDatabaseMissing('branches', ['id' => $deletableBranch->id], 'tenant');
});

<?php

declare(strict_types=1);

use App\Actions\Tenant\User\DeleteUserAction;
use App\Enums\BranchStatus;
use App\Enums\RoleName;
use App\Enums\UserDeletionResult;
use App\Enums\UserStatus;
use App\Http\Controllers\Tenant\UserController;
use App\Models\Branch;
use App\Models\Role;
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

function usersTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

/**
 * @return array{current: Branch, secondary: Branch, user: User}
 */
function authenticateUsersModuleUser(): array
{
    $currentBranch = Branch::query()->create([
        'code' => 'MAIN',
        'name' => 'Main Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $secondaryBranch = Branch::query()->create([
        'code' => 'ALT',
        'name' => 'Alt Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $user = User::query()->create([
        'branch_id' => $currentBranch->id,
        'name' => 'Module Admin',
        'email' => 'module.admin+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $currentBranch->id]);

    return [
        'current' => $currentBranch,
        'secondary' => $secondaryBranch,
        'user' => $user,
    ];
}

it('shows users index scoped to current branch', function (): void {
    $fixture = authenticateUsersModuleUser();

    User::query()->create([
        'branch_id' => $fixture['current']->id,
        'name' => 'Current Branch User',
        'email' => 'current+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    User::query()->create([
        'branch_id' => $fixture['secondary']->id,
        'name' => 'Secondary Branch User',
        'email' => 'secondary+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    $response = $this->get(usersTenantRoute('users.index'));

    $response->assertSuccessful();

    $items = $response->viewData('items');
    expect($items->total())->toBe(2);
});

it('filters users by search and status', function (): void {
    $fixture = authenticateUsersModuleUser();

    User::query()->create([
        'branch_id' => $fixture['current']->id,
        'name' => 'Searchable User',
        'email' => 'searchable+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::SUSPENDED->value,
    ]);

    User::query()->create([
        'branch_id' => $fixture['current']->id,
        'name' => 'Other User',
        'email' => 'other+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    $searchResponse = $this->get(usersTenantRoute('users.index', ['search' => 'Searchable']));
    $statusResponse = $this->get(usersTenantRoute('users.index', ['status' => UserStatus::SUSPENDED->value]));

    expect($searchResponse->viewData('items')->total())->toBe(1);
    expect($statusResponse->viewData('items')->total())->toBe(1);
});

it('clamps users pagination limits', function (): void {
    authenticateUsersModuleUser();

    $minResponse = $this->get(usersTenantRoute('users.index', ['per_page' => 1]));
    $maxResponse = $this->get(usersTenantRoute('users.index', ['per_page' => 999]));

    expect($minResponse->viewData('items')->perPage())->toBe(5);
    expect($maxResponse->viewData('items')->perPage())->toBe(100);
});

it('validates user create request payload', function (): void {
    authenticateUsersModuleUser();

    $response = $this->from(usersTenantRoute('users.create'))
        ->post(usersTenantRoute('users.store'), [
            'name' => '',
            'email' => 'bad-email',
            'password' => '123',
            'password_confirmation' => '456',
            'status' => '',
        ]);

    $response->assertRedirect(usersTenantRoute('users.create'));
    $response->assertSessionHasErrors(['name', 'email', 'password', 'status']);
});

it('throws not found when showing user outside current branch', function (): void {
    $fixture = authenticateUsersModuleUser();

    $foreignUser = User::query()->create([
        'branch_id' => $fixture['secondary']->id,
        'name' => 'Foreign User',
        'email' => 'foreign+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    $this->expectException(NotFoundHttpException::class);
    (new UserController())->show($foreignUser);
});

it('prevents deleting the last tenant owner user', function (): void {
    $fixture = authenticateUsersModuleUser();

    Role::query()->firstOrCreate([
        'name' => RoleName::TENANT_OWNER->value,
        'guard_name' => 'user',
    ]);

    $fixture['user']->assignRole(RoleName::TENANT_OWNER->value);

    $result = app(DeleteUserAction::class)->handle($fixture['user']);

    expect($result)->toBe(UserDeletionResult::LastTenantOwner);
    expect(User::query()->find($fixture['user']->id))->not->toBeNull();
});

it('allows deleting a tenant owner user when another tenant owner remains', function (): void {
    $fixture = authenticateUsersModuleUser();

    Role::query()->firstOrCreate([
        'name' => RoleName::TENANT_OWNER->value,
        'guard_name' => 'user',
    ]);

    $fixture['user']->assignRole(RoleName::TENANT_OWNER->value);

    $secondOwner = User::query()->create([
        'branch_id' => $fixture['current']->id,
        'name' => 'Second Owner',
        'email' => 'second.owner+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);
    $secondOwner->assignRole(RoleName::TENANT_OWNER->value);

    $result = app(DeleteUserAction::class)->handle($secondOwner);

    expect($result)->toBe(UserDeletionResult::Deleted);
    expect(User::query()->find($secondOwner->id))->toBeNull();
});

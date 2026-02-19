<?php

declare(strict_types=1);

use App\Enums\BranchStatus;
use App\Enums\UserStatus;
use App\Models\Branch;
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

function branchSwitchTenantRoute(): string
{
    return route('tenant.branch.switch', ['tenant' => 'test-tenant-id']);
}

/**
 * @return array{user: User, current: Branch, target: Branch, inactive: Branch}
 */
function authenticateBranchSwitchUser(): array
{
    $current = Branch::query()->create([
        'code' => 'MAIN',
        'name' => 'Main Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $target = Branch::query()->create([
        'code' => 'ALT',
        'name' => 'Alt Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $inactive = Branch::query()->create([
        'code' => 'INACTIVE',
        'name' => 'Inactive Branch',
        'status' => BranchStatus::INACTIVE->value,
    ]);

    $user = User::query()->create([
        'branch_id' => $current->id,
        'name' => 'Branch Switch User',
        'email' => 'branch.switch+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $current->id]);

    return ['user' => $user, 'current' => $current, 'target' => $target, 'inactive' => $inactive];
}

it('switches current branch to selected active branch', function (): void {
    $fixture = authenticateBranchSwitchUser();

    $response = $this->post(branchSwitchTenantRoute(), [
        'branch_id' => $fixture['target']->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status', 'Branch switched.');

    expect((string) session('tenant.current_branch_id'))->toBe($fixture['target']->id);
});

it('rejects switching to inactive branch', function (): void {
    $fixture = authenticateBranchSwitchUser();

    $response = $this->post(branchSwitchTenantRoute(), [
        'branch_id' => $fixture['inactive']->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Selected branch is not active.');

    expect((string) session('tenant.current_branch_id'))->toBe($fixture['current']->id);
});

it('validates branch switch payload', function (): void {
    authenticateBranchSwitchUser();

    $response = $this->from(route('tenant.dashboard', ['tenant' => 'test-tenant-id']))
        ->post(branchSwitchTenantRoute(), [
            'branch_id' => 'bad-uuid',
        ]);

    $response->assertRedirect(route('tenant.dashboard', ['tenant' => 'test-tenant-id']));
    $response->assertSessionHasErrors(['branch_id']);
});

<?php

declare(strict_types=1);

use App\Enums\BranchStatus;
use App\Enums\UserStatus;
use App\Models\Branch;
use App\Models\User;
use App\Support\HeaderContextCache;
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

/**
 * @return array{branch: Branch, user: User}
 */
function authenticateHeaderCacheUser(): array
{
    $branch = Branch::query()->create([
        'code' => 'MAIN',
        'name' => 'Main Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $user = User::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Header Cache User',
        'email' => 'header.cache+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $branch->id]);

    return ['branch' => $branch, 'user' => $user];
}

function headerCacheTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

test('header cache version is bumped by branch create update and delete', function (): void {
    $fixture = authenticateHeaderCacheUser();

    expect(HeaderContextCache::currentVersion('test-tenant-id'))->toBe(1);

    $this->post(headerCacheTenantRoute('branches.store'), [
        'code' => 'SUB',
        'name' => 'Sub Branch',
        'status' => BranchStatus::ACTIVE->value,
    ])->assertRedirect(headerCacheTenantRoute('branches.index'));

    expect(HeaderContextCache::currentVersion('test-tenant-id'))->toBe(2);
    $branch = Branch::query()->where('code', 'SUB')->firstOrFail();

    $this->put(headerCacheTenantRoute('branches.update', ['branch' => $branch->id]), [
        'code' => 'SUB',
        'name' => 'Sub Branch Updated',
        'status' => BranchStatus::ACTIVE->value,
    ])->assertRedirect(headerCacheTenantRoute('branches.index'));
    expect(HeaderContextCache::currentVersion('test-tenant-id'))->toBe(3);

    $this->post(headerCacheTenantRoute('branches.store'), [
        'code' => 'DEL',
        'name' => 'Delete Branch',
        'status' => BranchStatus::ACTIVE->value,
    ])->assertRedirect(headerCacheTenantRoute('branches.index'));
    expect(HeaderContextCache::currentVersion('test-tenant-id'))->toBe(4);

    $deletionBranch = Branch::query()->where('code', 'DEL')->firstOrFail();

    $this->delete(headerCacheTenantRoute('branches.destroy', ['branch' => $deletionBranch->id]))
        ->assertRedirect(headerCacheTenantRoute('branches.index'));
    expect(HeaderContextCache::currentVersion('test-tenant-id'))->toBe(5);
    expect((string) session('tenant.current_branch_id'))->toBe((string) $fixture['branch']->id);
});

test('header cache version is bumped when tenant settings are saved', function (): void {
    $fixture = authenticateHeaderCacheUser();

    expect(HeaderContextCache::currentVersion('test-tenant-id'))->toBe(1);

    $this->put(headerCacheTenantRoute('settings.update'), [
        'timezone' => 'Asia/Karachi',
    ])->assertRedirect(headerCacheTenantRoute('settings.edit'));

    expect(HeaderContextCache::currentVersion('test-tenant-id'))->toBe(2);

    $this->put(headerCacheTenantRoute('settings.update'), [
        'timezone' => 'UTC',
    ])->assertRedirect(headerCacheTenantRoute('settings.edit'));

    expect(HeaderContextCache::currentVersion('test-tenant-id'))->toBe(3);
});

<?php

declare(strict_types=1);

use App\Actions\Tenant\Branch\CreateBranchAction;
use App\Actions\Tenant\Branch\DeleteBranchAction;
use App\Actions\Tenant\Branch\UpdateBranchAction;
use App\Actions\Tenant\Setting\UpsertTenantSettingAction;
use App\Enums\BranchDeletionResult;
use App\Enums\BranchStatus;
use App\Enums\UserStatus;
use App\Models\Branch;
use App\Models\User;
use App\Support\HeaderContextCache;
use Illuminate\Session\Store;
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

test('header cache version is bumped by branch create update and delete', function (): void {
    $fixture = authenticateHeaderCacheUser();
    bindHeaderCacheTenantContext();

    expect(HeaderContextCache::currentVersion('test-tenant-id'))->toBe(1);

    $branch = app(CreateBranchAction::class)->handle([
        'code' => 'SUB',
        'name' => 'Sub Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    expect(HeaderContextCache::currentVersion('test-tenant-id'))->toBe(2);
    expect($branch->code)->toBe('SUB');

    $updated = app(UpdateBranchAction::class)->handle($branch, [
        'code' => 'SUB',
        'name' => 'Sub Branch Updated',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    expect($updated)->toBeTrue();
    expect(HeaderContextCache::currentVersion('test-tenant-id'))->toBe(3);

    $deletionBranch = app(CreateBranchAction::class)->handle([
        'code' => 'DEL',
        'name' => 'Delete Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);
    expect(HeaderContextCache::currentVersion('test-tenant-id'))->toBe(4);

    $deletionResult = app(DeleteBranchAction::class)->handle($deletionBranch, $fixture['branch']->id);

    expect($deletionResult)->toBe(BranchDeletionResult::Deleted);
    expect(HeaderContextCache::currentVersion('test-tenant-id'))->toBe(5);
});

test('header cache version is bumped when tenant settings are saved', function (): void {
    $fixture = authenticateHeaderCacheUser();
    bindHeaderCacheTenantContext();

    expect(HeaderContextCache::currentVersion('test-tenant-id'))->toBe(1);

    /** @var Store $session */
    $session = app('session')->driver();
    app(UpsertTenantSettingAction::class)->handle([
        'timezone' => 'Asia/Karachi',
    ], $fixture['branch']->id, $session, $fixture['user']);

    expect(HeaderContextCache::currentVersion('test-tenant-id'))->toBe(2);

    app(UpsertTenantSettingAction::class)->handle([
        'timezone' => 'UTC',
    ], $fixture['branch']->id, $session, $fixture['user']);

    expect(HeaderContextCache::currentVersion('test-tenant-id'))->toBe(3);
});

function bindHeaderCacheTenantContext(): void
{
    $route = app('router')->getRoutes()->match(
        request()->create('/firm/test-tenant-id/branches', 'POST')
    );

    request()->setRouteResolver(static fn () => $route);
}

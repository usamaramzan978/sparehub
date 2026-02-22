<?php

declare(strict_types=1);

use App\Enums\BranchStatus;
use App\Enums\LoginUserType;
use App\Enums\TwoFactorMethod;
use App\Enums\UserStatus;
use App\Models\Branch;
use App\Models\LoginMap;
use App\Models\TenantSetting;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
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
    Config::set('tenancy.database.central_connection', 'tenant');

    Artisan::call('migrate:fresh', [
        '--database' => 'tenant',
        '--path' => database_path('migrations/tenant'),
        '--realpath' => true,
        '--force' => true,
    ]);
    Artisan::call('migrate', [
        '--database' => 'tenant',
        '--path' => database_path('migrations/2019_09_15_000010_create_tenants_table.php'),
        '--realpath' => true,
        '--force' => true,
    ]);
    Artisan::call('migrate', [
        '--database' => 'tenant',
        '--path' => database_path('migrations/2026_02_16_170521_create_login_maps_table.php'),
        '--realpath' => true,
        '--force' => true,
    ]);
    Artisan::call('migrate', [
        '--database' => 'tenant',
        '--path' => database_path('migrations/2026_02_16_000001_create_login_attempts_table.php'),
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
 * @return array{primary: Branch, secondary: Branch, user: User}
 */
function createAuditFixture(): array
{
    $primary = Branch::query()->create([
        'code' => 'MAIN',
        'name' => 'Main Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $secondary = Branch::query()->create([
        'code' => 'ALT',
        'name' => 'Alt Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $user = User::query()->create([
        'branch_id' => $primary->id,
        'name' => 'Audit User',
        'email' => 'audit.user+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    return ['primary' => $primary, 'secondary' => $secondary, 'user' => $user];
}

it('logs settings updates and two-factor policy changes in activity timeline', function (): void {
    $fixture = createAuditFixture();

    TenantSetting::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['primary']->id,
        'two_factor_enabled' => false,
        'two_factor_method' => null,
    ]);

    $this->actingAs($fixture['user'], 'user');
    $this->withSession(['tenant.current_branch_id' => $fixture['primary']->id]);

    $this->put(route('tenant.settings.update', ['tenant' => 'test-tenant-id']), [
        'company_name' => 'Updated Company',
        'two_factor_enabled' => '1',
        'two_factor_method' => TwoFactorMethod::EMAIL->value,
    ])->assertRedirect(route('tenant.settings.edit', ['tenant' => 'test-tenant-id']));

    $events = DB::connection('tenant')
        ->table('tenant_activity_timelines')
        ->pluck('event')
        ->all();

    expect($events)->toContain('settings_updated');
    expect($events)->toContain('two_factor_policy_changed');
});

it('logs branch switch events in activity timeline', function (): void {
    $fixture = createAuditFixture();

    $this->actingAs($fixture['user'], 'user');
    $this->withSession(['tenant.current_branch_id' => $fixture['primary']->id]);

    $this->post(route('tenant.branch.switch', ['tenant' => 'test-tenant-id']), [
        'branch_id' => $fixture['secondary']->id,
    ])->assertSessionHas('status', 'Branch switched.');

    $event = DB::connection('tenant')
        ->table('tenant_activity_timelines')
        ->where('event', 'branch_switched')
        ->first();

    expect($event)->not->toBeNull();
});

it('logs tenant authentication failures in activity timeline', function (): void {
    $tenantId = 'audit-tenant-id';
    DB::connection('tenant')->table('tenants')->insert([
        'id' => $tenantId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    LoginMap::query()->create([
        'tenant_id' => $tenantId,
        'type_id' => (string) Str::uuid(),
        'type' => LoginUserType::USER->value,
        'email' => 'audit.login@example.test',
        'password' => Hash::make('correct-password'),
        'status' => true,
    ]);

    $this->post(route('auth.login.submit'), [
        'email' => 'audit.login@example.test',
        'password' => 'wrong-password',
    ])->assertSessionHasErrors(['email']);

    $event = DB::connection('tenant')
        ->table('tenant_activity_timelines')
        ->where('event', 'auth_failure')
        ->latest('id')
        ->first();

    expect($event)->not->toBeNull();
    expect($event->description)->toBe('Tenant login attempt failed.');
});

<?php

declare(strict_types=1);

use App\Enums\BranchStatus;
use App\Enums\UserStatus;
use App\Models\Branch;
use App\Models\TenantSetting;
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

function settingsTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

/**
 * @return array{branch: Branch, user: User}
 */
function authenticateSettingsModuleUser(): array
{
    $branch = Branch::query()->create([
        'code' => 'MAIN',
        'name' => 'Main Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $user = User::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Settings User',
        'email' => 'settings.user+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $branch->id]);

    return ['branch' => $branch, 'user' => $user];
}

it('shows settings edit page', function (): void {
    authenticateSettingsModuleUser();

    $response = $this->get(settingsTenantRoute('settings.edit'));

    $response->assertSuccessful();
    $response->assertSee('Settings');
});

it('creates tenant settings on first update', function (): void {
    $fixture = authenticateSettingsModuleUser();

    $response = $this->put(settingsTenantRoute('settings.update'), [
        'company_name' => 'SpareHub',
        'support_email' => 'support@example.test',
        'support_phone' => '12345',
        'notify_email' => '1',
        'enable_otp' => '1',
        'otp_length' => 6,
        'otp_expiry_minutes' => 10,
    ]);

    $response->assertRedirect(settingsTenantRoute('settings.edit'));

    $settings = TenantSetting::query()->where('branch_id', $fixture['branch']->id)->firstOrFail();
    expect($settings->company_name)->toBe('SpareHub');
    expect($settings->support_email)->toBe('support@example.test');
    expect($settings->enable_otp)->toBeTrue();
});

it('updates existing tenant settings row', function (): void {
    $fixture = authenticateSettingsModuleUser();

    $settings = TenantSetting::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['branch']->id,
        'company_name' => 'Old Name',
    ]);

    $response = $this->put(settingsTenantRoute('settings.update'), [
        'company_name' => 'New Name',
    ]);

    $response->assertRedirect(settingsTenantRoute('settings.edit'));

    $settings->refresh();
    expect($settings->company_name)->toBe('New Name');
});

it('validates settings fields', function (): void {
    authenticateSettingsModuleUser();

    $response = $this->from(settingsTenantRoute('settings.edit'))
        ->put(settingsTenantRoute('settings.update'), [
            'support_email' => 'invalid-email',
            'otp_length' => 3,
            'otp_expiry_minutes' => 130,
        ]);

    $response->assertRedirect(settingsTenantRoute('settings.edit'));
    $response->assertSessionHasErrors(['support_email', 'otp_length', 'otp_expiry_minutes']);
});

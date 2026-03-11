<?php

declare(strict_types=1);

use App\Enums\BranchStatus;
use App\Enums\TwoFactorMethod;
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
    $response->assertSee('Settings User');
});

it('creates tenant settings on first update', function (): void {
    $fixture = authenticateSettingsModuleUser();

    $response = $this->put(settingsTenantRoute('settings.update'), [
        'company_name' => 'SpareHub',
        'support_email' => 'support@example.test',
        'support_phone' => '12345',
        'timezone' => 'Asia/Karachi',
        'email_notifications_enabled' => '1',
        'customer_display_enabled' => '1',
        'two_factor_enabled' => '1',
        'two_factor_method' => TwoFactorMethod::EMAIL->value,
    ]);

    $response->assertRedirect(settingsTenantRoute('settings.edit'));

    $settings = TenantSetting::query()->where('branch_id', $fixture['branch']->id)->firstOrFail();
    expect($settings->company_name)->toBe('SpareHub');
    expect($settings->support_email)->toBe('support@example.test');
    expect($settings->timezone)->toBe('Asia/Karachi');
    expect($settings->customer_display_enabled)->toBeTrue();
    expect($settings->two_factor_enabled)->toBeTrue();
    expect($settings->two_factor_method)->toBe(TwoFactorMethod::EMAIL);
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
            'two_factor_enabled' => '1',
        ]);

    $response->assertRedirect(settingsTenantRoute('settings.edit'));
    $response->assertSessionHasErrors(['support_email', 'two_factor_method']);
});

it('validates allowed two-factor methods', function (): void {
    authenticateSettingsModuleUser();

    $response = $this->from(settingsTenantRoute('settings.edit'))
        ->put(settingsTenantRoute('settings.update'), [
            'two_factor_enabled' => '1',
            'two_factor_method' => 'sms',
        ]);

    $response->assertRedirect(settingsTenantRoute('settings.edit'));
    $response->assertSessionHasErrors(['two_factor_method']);
});

it('validates timezone values', function (): void {
    authenticateSettingsModuleUser();

    $response = $this->from(settingsTenantRoute('settings.edit'))
        ->put(settingsTenantRoute('settings.update'), [
            'timezone' => 'Mars/Phobos',
        ]);

    $response->assertRedirect(settingsTenantRoute('settings.edit'));
    $response->assertSessionHasErrors(['timezone']);
});

it('clears two-factor method when two-factor is disabled', function (): void {
    $fixture = authenticateSettingsModuleUser();

    $settings = TenantSetting::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['branch']->id,
        'two_factor_enabled' => true,
        'two_factor_method' => TwoFactorMethod::AUTHENTICATOR->value,
    ]);

    $response = $this->put(settingsTenantRoute('settings.update'), [
        'two_factor_enabled' => '0',
        'two_factor_method' => TwoFactorMethod::EMAIL->value,
    ]);

    $response->assertRedirect(settingsTenantRoute('settings.edit'));

    $settings->refresh();
    expect($settings->two_factor_enabled)->toBeFalse();
    expect($settings->two_factor_method)->toBeNull();
});

it('shows security-page guidance instead of authenticator qr details in settings', function (): void {
    authenticateSettingsModuleUser();

    $this->put(settingsTenantRoute('settings.update'), [
        'two_factor_enabled' => '1',
        'two_factor_method' => TwoFactorMethod::AUTHENTICATOR->value,
    ])->assertRedirect(settingsTenantRoute('settings.edit'));

    $response = $this->get(settingsTenantRoute('settings.edit'));

    $response->assertSuccessful();
    $response->assertSee('Open Security Page');
    $response->assertDontSee('Manual key:');
});

it('applies tenant timezone from settings on tenant requests', function (): void {
    $fixture = authenticateSettingsModuleUser();

    TenantSetting::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['branch']->id,
        'timezone' => 'Asia/Karachi',
    ]);

    $this->get(settingsTenantRoute('settings.edit'))->assertSuccessful();

    expect(config('app.timezone'))->toBe('Asia/Karachi');
});

it('allows settings access while authenticator enrollment is pending', function (): void {
    $fixture = authenticateSettingsModuleUser();

    TenantSetting::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['branch']->id,
        'two_factor_enabled' => true,
        'two_factor_method' => TwoFactorMethod::AUTHENTICATOR->value,
    ]);

    $this->withSession([
        'two_step.required' => true,
        'two_step.verified' => false,
        'two_step.enrollment_required' => true,
        'two_step.setup_required' => true,
        'two_step.method' => TwoFactorMethod::AUTHENTICATOR->value,
    ]);

    $response = $this->get(settingsTenantRoute('settings.edit'));

    $response->assertSuccessful();
    $response->assertSee('System Settings');
});

it('clears pending two-step lock flags when two-factor is disabled from settings', function (): void {
    $fixture = authenticateSettingsModuleUser();

    TenantSetting::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['branch']->id,
        'two_factor_enabled' => true,
        'two_factor_method' => TwoFactorMethod::AUTHENTICATOR->value,
    ]);

    $this->withSession([
        'two_step.required' => true,
        'two_step.verified' => false,
        'two_step.enrollment_required' => true,
        'two_step.setup_required' => true,
        'two_step.method' => TwoFactorMethod::AUTHENTICATOR->value,
        'two_step.code' => '123456',
        'two_step.expires_at' => now()->addMinute(),
    ]);

    $response = $this->put(settingsTenantRoute('settings.update'), [
        'two_factor_enabled' => '0',
        'two_factor_method' => TwoFactorMethod::AUTHENTICATOR->value,
    ]);

    $response->assertRedirect(settingsTenantRoute('settings.edit'));
    $response->assertSessionHas('two_step.required', false);
    $response->assertSessionHas('two_step.verified', true);
    $response->assertSessionMissing('two_step.method');
    $response->assertSessionMissing('two_step.setup_required');
    $response->assertSessionMissing('two_step.enrollment_required');
    $response->assertSessionMissing('two_step.code');
    $response->assertSessionMissing('two_step.expires_at');
});

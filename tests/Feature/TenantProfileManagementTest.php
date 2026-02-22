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

function profileTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

function authenticateProfileModuleUser(): User
{
    $branch = Branch::query()->create([
        'code' => 'MAIN',
        'name' => 'Main Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $user = User::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Profile User',
        'email' => 'profile.user+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password123'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $branch->id]);

    return $user;
}

it('shows profile page', function (): void {
    $user = authenticateProfileModuleUser();

    $response = $this->get(profileTenantRoute('profile.show'));

    $response->assertSuccessful();
    $response->assertSee($user->name);
});

it('updates profile data and normalizes email', function (): void {
    $user = authenticateProfileModuleUser();

    $response = $this->put(profileTenantRoute('profile.update'), [
        'name' => 'Updated Name',
        'email' => 'UPDATED.EMAIL@EXAMPLE.TEST',
        'phone' => '1234567',
    ]);

    $response->assertRedirect(profileTenantRoute('profile.show'));

    $user->refresh();
    expect($user->name)->toBe('Updated Name');
    expect($user->email)->toBe('updated.email@example.test');
    expect($user->phone)->toBe('1234567');
});

it('validates profile password confirmation', function (): void {
    authenticateProfileModuleUser();

    $response = $this->from(profileTenantRoute('profile.edit'))
        ->put(profileTenantRoute('profile.update'), [
            'name' => 'Profile User',
            'email' => 'profile@example.test',
            'password' => 'newpassword',
            'password_confirmation' => 'different',
        ]);

    $response->assertRedirect(profileTenantRoute('profile.edit'));
    $response->assertSessionHasErrors(['password']);
});

it('shows profile security page', function (): void {
    $fixtureUser = authenticateProfileModuleUser();

    TenantSetting::query()->create([
        'branch_id' => (string) $fixtureUser->branch_id,
        'two_factor_enabled' => true,
        'two_factor_method' => TwoFactorMethod::AUTHENTICATOR->value,
    ]);

    $response = $this->get(profileTenantRoute('profile.security.show'));

    $response->assertSuccessful();
    $response->assertSee('Profile Security');
    $response->assertSee('Start Authenticator Setup');
});

it('starts authenticator setup and stores recovery codes', function (): void {
    $fixtureUser = authenticateProfileModuleUser();

    TenantSetting::query()->create([
        'branch_id' => (string) $fixtureUser->branch_id,
        'two_factor_enabled' => true,
        'two_factor_method' => TwoFactorMethod::AUTHENTICATOR->value,
    ]);

    $response = $this->post(profileTenantRoute('profile.security.authenticator.setup'));

    $response->assertRedirect(profileTenantRoute('profile.security.show'));

    $fixtureUser->refresh();
    expect($fixtureUser->two_factor_secret)->not->toBeNull()
        ->and($fixtureUser->two_factor_verified_at)->toBeNull()
        ->and($fixtureUser->two_factor_recovery_codes)->toBeArray()
        ->and(count($fixtureUser->two_factor_recovery_codes))->toBe(8);
});

it('regenerates backup codes from security page', function (): void {
    $fixtureUser = authenticateProfileModuleUser();

    TenantSetting::query()->create([
        'branch_id' => (string) $fixtureUser->branch_id,
        'two_factor_enabled' => true,
        'two_factor_method' => TwoFactorMethod::AUTHENTICATOR->value,
    ]);

    $this->post(profileTenantRoute('profile.security.authenticator.setup'))
        ->assertRedirect(profileTenantRoute('profile.security.show'));

    $before = User::query()->findOrFail($fixtureUser->id);
    $codesBefore = $before->two_factor_recovery_codes;

    $response = $this->post(profileTenantRoute('profile.security.backup-codes.regenerate'));

    $response->assertRedirect(profileTenantRoute('profile.security.show'));

    $fixtureUser->refresh();
    expect($fixtureUser->two_factor_recovery_codes)->toBeArray()
        ->and(count($fixtureUser->two_factor_recovery_codes))->toBe(8)
        ->and($fixtureUser->two_factor_recovery_codes)->not->toBe($codesBefore);
});

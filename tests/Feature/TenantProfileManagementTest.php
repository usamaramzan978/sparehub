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

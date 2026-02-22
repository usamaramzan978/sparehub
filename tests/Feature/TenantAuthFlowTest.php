<?php

declare(strict_types=1);

use App\Enums\BranchStatus;
use App\Enums\LoginUserType;
use App\Enums\TenantStatus;
use App\Enums\TwoFactorMethod;
use App\Enums\UserStatus;
use App\Mail\TenantTwoStepCodeMail;
use App\Models\Branch;
use App\Models\LoginMap;
use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Stancl\Tenancy\Facades\Tenancy;
use Stancl\Tenancy\Middleware\InitializeTenancyByPath;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Config::set('tenancy.bootstrappers', []);

    Config::set('database.connections.tenant', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]);

    Artisan::call('migrate:fresh', [
        '--database' => 'tenant',
        '--path' => database_path('migrations/tenant'),
        '--realpath' => true,
        '--force' => true,
    ]);
});

afterEach(function (): void {
    if (tenancy()->initialized) {
        Tenancy::end();
    }
});

function makeTenant(string $name): Tenant
{
    return Tenant::withoutEvents(fn (): Tenant => Tenant::query()->create([
        'id' => (string) Str::uuid(),
        'name' => $name,
        'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
        'status' => TenantStatus::ACTIVE->value,
    ]));
}

function makeLoginMap(Tenant $tenant, string $email, string $plainPassword, ?string $typeId = null, bool $status = true): array
{
    $userId = $typeId ?? (string) Str::uuid();

    LoginMap::query()->create([
        'tenant_id' => $tenant->id,
        'type_id' => $userId,
        'type' => LoginUserType::USER->value,
        'email' => $email,
        'password' => Hash::make($plainPassword),
        'status' => $status,
    ]);

    return [
        'type_id' => $userId,
        'tenant_id' => $tenant->id,
    ];
}

it('redirects directly to signed authenticate URL for single-tenant credentials', function (): void {
    $tenant = makeTenant('Single Tenant');
    makeLoginMap($tenant, 'single@test.local', 'secret-pass');

    $response = $this->post(route('auth.login.submit'), [
        'email' => 'single@test.local',
        'password' => 'secret-pass',
    ]);

    $response->assertRedirect();

    $location = (string) $response->headers->get('Location');
    expect($location)->toContain(sprintf('/firm/%s/authenticate', $tenant->id))
        ->toContain('nonce=')
        ->toContain('signature=');
});

it('redirects to choose-tenant when credentials match multiple tenants', function (): void {
    $tenantA = makeTenant('Tenant Alpha');
    $tenantB = makeTenant('Tenant Beta');

    makeLoginMap($tenantA, 'shared@test.local', 'shared-pass');
    makeLoginMap($tenantB, 'shared@test.local', 'shared-pass');

    $response = $this->post(route('auth.login.submit'), [
        'email' => 'shared@test.local',
        'password' => 'shared-pass',
        'remember' => '1',
    ]);

    $response->assertRedirect(route('auth.choose-tenant'));
    $response->assertSessionHas('multi_tenant_login.tenants');
    $response->assertSessionHas('multi_tenant_login.remember', true);
});

it('shows choose-tenant page with matched tenant names', function (): void {
    $tenantA = makeTenant('Tenant Alpha');
    $tenantB = makeTenant('Tenant Beta');

    $response = $this->withSession([
        'multi_tenant_login' => [
            'tenants' => [
                ['tenant_id' => $tenantA->id, 'tenant_name' => $tenantA->name, 'type_id' => (string) Str::uuid(), 'type' => 'user'],
                ['tenant_id' => $tenantB->id, 'tenant_name' => $tenantB->name, 'type_id' => (string) Str::uuid(), 'type' => 'user'],
            ],
            'remember' => true,
            'expires_at' => now()->addMinutes(5),
        ],
    ])->get(route('auth.choose-tenant'));

    $response->assertSuccessful();
    $response->assertSee('Choose Workspace');
    $response->assertSee('Tenant Alpha');
    $response->assertSee('Tenant Beta');
});

it('expires choose-tenant session after timeout', function (): void {
    $response = $this->withSession([
        'multi_tenant_login' => [
            'tenants' => [],
            'remember' => false,
            'expires_at' => now()->subMinute(),
        ],
    ])->get(route('auth.choose-tenant'));

    $response->assertRedirect(route('auth.login'));
    $response->assertSessionHasErrors(['email']);
});

it('rejects unauthorized tenant selection from choose-tenant', function (): void {
    $allowedTenant = makeTenant('Allowed Tenant');

    $response = $this->withSession([
        'multi_tenant_login' => [
            'tenants' => [[
                'tenant_id' => $allowedTenant->id,
                'tenant_name' => $allowedTenant->name,
                'type_id' => (string) Str::uuid(),
                'type' => LoginUserType::USER->value,
            ]],
            'remember' => false,
            'expires_at' => now()->addMinutes(5),
        ],
    ])->post(route('auth.choose-tenant.submit'), [
        'tenant_id' => (string) Str::uuid(),
    ]);

    $response->assertForbidden();
});

it('creates signed authenticate redirect for valid tenant selection', function (): void {
    $tenant = makeTenant('Tenant Select');

    $response = $this->withSession([
        'multi_tenant_login' => [
            'tenants' => [[
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'type_id' => (string) Str::uuid(),
                'type' => LoginUserType::USER->value,
            ]],
            'remember' => true,
            'expires_at' => now()->addMinutes(5),
        ],
    ])->post(route('auth.choose-tenant.submit'), [
        'tenant_id' => $tenant->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionMissing('multi_tenant_login');

    $location = (string) $response->headers->get('Location');
    expect($location)->toContain(sprintf('/firm/%s/authenticate', $tenant->id))
        ->toContain('nonce=')
        ->toContain('signature=');
});

it('authenticates tenant user from a valid signed URL nonce payload', function (): void {
    $this->withoutMiddleware([
        InitializeTenancyByPath::class,
        PreventAccessFromCentralDomains::class,
    ]);

    $tenant = makeTenant('Tenant Auth');
    Tenancy::initialize($tenant);

    $user = User::query()->create([
        'name' => 'Tenant User',
        'email' => 'tenant.user@test.local',
        'password' => Hash::make('secret-pass'),
        'status' => UserStatus::ACTIVE->value,
    ]);
    $userId = (string) $user->id;

    LoginMap::query()->create([
        'tenant_id' => $tenant->id,
        'type_id' => $userId,
        'type' => LoginUserType::USER->value,
        'email' => $user->email,
        'password' => Hash::make('secret-pass'),
        'status' => true,
    ]);

    $nonce = (string) Str::uuid();
    Cache::store('database')->put('login_nonce:'.$nonce, [
        'type_id' => $userId,
        'type' => LoginUserType::USER->value,
        'remember' => true,
        'tenant_id' => $tenant->id,
    ], now()->addSeconds(30));

    $signedUrl = URL::temporarySignedRoute(
        'tenant.authenticate',
        now()->addSeconds(30),
        ['tenant' => $tenant->id, 'nonce' => $nonce]
    );

    $response = $this->get($signedUrl);

    $response->assertRedirect(route('tenant.dashboard', ['tenant' => $tenant->id]));
    $this->assertAuthenticated('user');
});

it('rejects invalid or expired signed authenticate URLs', function (): void {
    $this->withoutMiddleware([
        InitializeTenancyByPath::class,
        PreventAccessFromCentralDomains::class,
    ]);

    $tenant = makeTenant('Tenant Signed');
    Tenancy::initialize($tenant);

    $expiredSignedUrl = URL::temporarySignedRoute(
        'tenant.authenticate',
        now()->subSecond(),
        ['tenant' => $tenant->id, 'nonce' => (string) Str::uuid()]
    );

    $response = $this->get($expiredSignedUrl);

    $response->assertForbidden();
});

it('prevents nonce replay on authenticate endpoint', function (): void {
    $this->withoutMiddleware([
        InitializeTenancyByPath::class,
        PreventAccessFromCentralDomains::class,
    ]);

    $tenant = makeTenant('Tenant Replay');
    Tenancy::initialize($tenant);

    $user = User::query()->create([
        'name' => 'Replay User',
        'email' => 'replay.user@test.local',
        'password' => Hash::make('secret-pass'),
        'status' => UserStatus::ACTIVE->value,
    ]);
    $userId = (string) $user->id;

    LoginMap::query()->create([
        'tenant_id' => $tenant->id,
        'type_id' => $userId,
        'type' => LoginUserType::USER->value,
        'email' => 'replay.user@test.local',
        'password' => Hash::make('secret-pass'),
        'status' => true,
    ]);

    $nonce = (string) Str::uuid();
    Cache::store('database')->put('login_nonce:'.$nonce, [
        'type_id' => $userId,
        'type' => LoginUserType::USER->value,
        'remember' => false,
        'tenant_id' => $tenant->id,
    ], now()->addSeconds(30));

    $signedUrl = URL::temporarySignedRoute(
        'tenant.authenticate',
        now()->addSeconds(30),
        ['tenant' => $tenant->id, 'nonce' => $nonce]
    );

    $this->get($signedUrl)->assertRedirect(route('tenant.dashboard', ['tenant' => $tenant->id]));

    $replayResponse = $this->get($signedUrl);
    $replayResponse->assertRedirect(route('tenant.login', ['tenant' => $tenant->id]));
});

it('redirects to two-step and sends email code when tenant uses email two-factor', function (): void {
    $this->withoutMiddleware([
        InitializeTenancyByPath::class,
        PreventAccessFromCentralDomains::class,
    ]);

    Mail::fake();

    $tenant = makeTenant('Tenant Email 2FA');
    Tenancy::initialize($tenant);

    $branch = Branch::query()->create([
        'code' => 'MAIN',
        'name' => 'Main Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $user = User::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Email 2FA User',
        'email' => 'email.2fa@test.local',
        'password' => Hash::make('secret-pass'),
        'status' => UserStatus::ACTIVE->value,
    ]);
    $userId = (string) $user->id;

    LoginMap::query()->create([
        'tenant_id' => $tenant->id,
        'type_id' => $userId,
        'type' => LoginUserType::USER->value,
        'email' => $user->email,
        'password' => Hash::make('secret-pass'),
        'status' => true,
    ]);

    TenantSetting::query()->create([
        'branch_id' => $branch->id,
        'two_factor_enabled' => true,
        'two_factor_method' => TwoFactorMethod::EMAIL->value,
    ]);

    $nonce = (string) Str::uuid();
    Cache::store('database')->put('login_nonce:'.$nonce, [
        'type_id' => $userId,
        'type' => LoginUserType::USER->value,
        'remember' => false,
        'tenant_id' => $tenant->id,
    ], now()->addSeconds(30));

    $signedUrl = URL::temporarySignedRoute(
        'tenant.authenticate',
        now()->addSeconds(30),
        ['tenant' => $tenant->id, 'nonce' => $nonce]
    );

    $response = $this->get($signedUrl);

    $response->assertRedirect(route('tenant.two-step', ['tenant' => $tenant->id]));
    $response->assertSessionHas('two_step.required', true);
    $response->assertSessionHas('two_step.method', TwoFactorMethod::EMAIL->value);
    Mail::assertSent(TenantTwoStepCodeMail::class);

    $event = DB::connection('tenant')
        ->table('tenant_activity_timelines')
        ->where('event', 'two_step_code_issued')
        ->latest('id')
        ->first();

    expect($event)->not->toBeNull();
});

it('redirects to profile security enrollment when authenticator setup is incomplete', function (): void {
    $this->withoutMiddleware([
        InitializeTenancyByPath::class,
        PreventAccessFromCentralDomains::class,
    ]);

    $tenant = makeTenant('Tenant App 2FA');
    Tenancy::initialize($tenant);

    $branch = Branch::query()->create([
        'code' => 'MAIN',
        'name' => 'Main Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $user = User::query()->create([
        'branch_id' => $branch->id,
        'name' => 'App 2FA User',
        'email' => 'app.2fa@test.local',
        'password' => Hash::make('secret-pass'),
        'status' => UserStatus::ACTIVE->value,
        'two_factor_secret' => null,
        'two_factor_verified_at' => null,
    ]);
    $userId = (string) $user->id;

    LoginMap::query()->create([
        'tenant_id' => $tenant->id,
        'type_id' => $userId,
        'type' => LoginUserType::USER->value,
        'email' => $user->email,
        'password' => Hash::make('secret-pass'),
        'status' => true,
    ]);

    TenantSetting::query()->create([
        'branch_id' => $branch->id,
        'two_factor_enabled' => true,
        'two_factor_method' => TwoFactorMethod::AUTHENTICATOR->value,
    ]);

    $nonce = (string) Str::uuid();
    Cache::store('database')->put('login_nonce:'.$nonce, [
        'type_id' => $userId,
        'type' => LoginUserType::USER->value,
        'remember' => false,
        'tenant_id' => $tenant->id,
    ], now()->addSeconds(30));

    $signedUrl = URL::temporarySignedRoute(
        'tenant.authenticate',
        now()->addSeconds(30),
        ['tenant' => $tenant->id, 'nonce' => $nonce]
    );

    $response = $this->get($signedUrl);

    $response->assertRedirect(route('tenant.profile.security.show', ['tenant' => $tenant->id]));
    $response->assertSessionHas('two_step.required', true);
    $response->assertSessionHas('two_step.method', TwoFactorMethod::AUTHENTICATOR->value);
    $response->assertSessionHas('two_step.setup_required', true);
    $response->assertSessionHas('two_step.enrollment_required', true);

    $user->refresh();
    expect($user->two_factor_secret)->toBeNull();
});

it('does not show qr details on two-step page for already enrolled authenticator users', function (): void {
    $this->withoutMiddleware([
        InitializeTenancyByPath::class,
        PreventAccessFromCentralDomains::class,
    ]);

    $tenant = makeTenant('Tenant App 2FA Enrolled');
    Tenancy::initialize($tenant);

    $branch = Branch::query()->create([
        'code' => 'MAIN',
        'name' => 'Main Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $user = User::query()->create([
        'branch_id' => $branch->id,
        'name' => 'App 2FA Enrolled User',
        'email' => 'app.2fa.enrolled@test.local',
        'password' => Hash::make('secret-pass'),
        'status' => UserStatus::ACTIVE->value,
        'two_factor_type' => 'app',
        'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
        'two_factor_verified_at' => now(),
    ]);

    test()->actingAs($user, 'user');
    test()->withSession([
        'two_step.required' => true,
        'two_step.verified' => false,
        'two_step.method' => TwoFactorMethod::AUTHENTICATOR->value,
        'two_step.setup_required' => false,
    ]);

    $response = $this->get(route('tenant.two-step', ['tenant' => $tenant->id]));

    $response->assertSuccessful();
    $response->assertDontSee('Manual key:');
    $response->assertDontSee('Scan this QR code');
});

<?php

declare(strict_types=1);

use App\Enums\BranchStatus;
use App\Enums\BrandStatus;
use App\Models\Branch;
use App\Models\Brand;
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

function brandsTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

function authenticateBrandUser(): void
{
    $branch = Branch::query()->create([
        'code' => 'MAIN',
        'name' => 'Main Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $user = User::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Brand User',
        'email' => 'brand.user+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => 'active',
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $branch->id]);
}

it('shows brands index', function (): void {
    authenticateBrandUser();

    Brand::query()->create([
        'name' => 'Bosch',
        'slug' => 'bosch',
        'status' => BrandStatus::ACTIVE->value,
    ]);

    $response = $this->get(brandsTenantRoute('brands.index'));

    $response->assertSuccessful();
    $response->assertSee('Brands');
    $response->assertSee('Bosch');
});

it('filters brands by search text', function (): void {
    authenticateBrandUser();

    Brand::query()->create([
        'name' => 'Toyota Genuine',
        'slug' => 'toyota-genuine',
        'status' => BrandStatus::ACTIVE->value,
    ]);

    Brand::query()->create([
        'name' => 'Honda OEM',
        'slug' => 'honda-oem',
        'status' => BrandStatus::ACTIVE->value,
    ]);

    $response = $this->get(brandsTenantRoute('brands.index', ['search' => 'Toyota']));

    $response->assertSuccessful();
    $response->assertSee('Toyota Genuine');
    $response->assertDontSee('Honda OEM');
});

it('stores brand and generates slug', function (): void {
    authenticateBrandUser();

    $response = $this->post(brandsTenantRoute('brands.store'), [
        'name' => 'Mitsubishi',
        'status' => BrandStatus::ACTIVE->value,
    ]);

    $response->assertRedirect(brandsTenantRoute('brands.index'));
    $response->assertSessionHas('status', 'Created.');

    $this->assertDatabaseHas('brands', [
        'name' => 'Mitsubishi',
        'slug' => 'mitsubishi',
        'status' => BrandStatus::ACTIVE->value,
    ], 'tenant');
});

it('creates unique slug when brand names repeat', function (): void {
    authenticateBrandUser();

    Brand::query()->create([
        'name' => 'Nissan @',
        'slug' => 'nissan',
        'status' => BrandStatus::ACTIVE->value,
    ]);

    $this->post(brandsTenantRoute('brands.store'), [
        'name' => 'Nissan',
        'status' => BrandStatus::ACTIVE->value,
    ])->assertRedirect(brandsTenantRoute('brands.index'));

    $this->assertDatabaseHas('brands', [
        'name' => 'Nissan',
        'slug' => 'nissan-2',
    ], 'tenant');
});

it('validates required brand fields', function (string $field): void {
    authenticateBrandUser();

    $payload = [
        'name' => 'Suzuki',
        'status' => BrandStatus::ACTIVE->value,
    ];

    unset($payload[$field]);

    $response = $this->from(brandsTenantRoute('brands.index'))
        ->post(brandsTenantRoute('brands.store'), $payload);

    $response->assertRedirect(brandsTenantRoute('brands.index'));
    $response->assertSessionHasErrors([$field]);
})->with([
    'name' => 'name',
    'status' => 'status',
]);

it('validates unique brand name', function (): void {
    authenticateBrandUser();

    Brand::query()->create([
        'name' => 'Hyundai',
        'slug' => 'hyundai',
        'status' => BrandStatus::ACTIVE->value,
    ]);

    $response = $this->from(brandsTenantRoute('brands.index'))
        ->post(brandsTenantRoute('brands.store'), [
            'name' => 'Hyundai',
            'status' => BrandStatus::ACTIVE->value,
        ]);

    $response->assertRedirect(brandsTenantRoute('brands.index'));
    $response->assertSessionHasErrors(['name' => 'This brand name already exists.']);
});

it('clamps brands pagination limits', function (): void {
    authenticateBrandUser();

    $minResponse = $this->get(brandsTenantRoute('brands.index', ['per_page' => 1]));
    $maxResponse = $this->get(brandsTenantRoute('brands.index', ['per_page' => 999]));

    expect($minResponse->viewData('items')->perPage())->toBe(5);
    expect($maxResponse->viewData('items')->perPage())->toBe(100);
});

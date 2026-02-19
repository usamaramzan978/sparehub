<?php

declare(strict_types=1);

use App\Enums\BranchStatus;
use App\Enums\RecordStatus;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
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

function categoriesTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

function authenticateCategoryUser(): void
{
    $branch = Branch::query()->create([
        'code' => 'MAIN',
        'name' => 'Main Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $user = User::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Category User',
        'email' => 'category.user+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => 'active',
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $branch->id]);
}

it('shows categories index', function (): void {
    authenticateCategoryUser();

    $category = Category::query()->create([
        'name' => 'Engine',
        'slug' => 'engine',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    Product::query()->create([
        'category_id' => $category->id,
        'sku' => 'CAT-PROD-1',
        'name' => 'Category Product',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $response = $this->get(categoriesTenantRoute('categories.index'));

    $response->assertSuccessful();
    $response->assertSee('Categories');
    $response->assertSee('Engine');
    $response->assertSee('1 Products');
});

it('filters categories by search', function (): void {
    authenticateCategoryUser();

    Category::query()->create([
        'name' => 'Engine Parts',
        'slug' => 'engine-parts',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    Category::query()->create([
        'name' => 'Brake Parts',
        'slug' => 'brake-parts',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $response = $this->get(categoriesTenantRoute('categories.index', ['search' => 'Engine']));

    $response->assertSuccessful();
    $response->assertSee('Engine Parts');
});

it('stores category and generates slug', function (): void {
    authenticateCategoryUser();

    $response = $this->post(categoriesTenantRoute('categories.store'), [
        'name' => 'Suspension',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $response->assertRedirect(categoriesTenantRoute('categories.index'));
    $response->assertSessionHas('status', 'Created.');

    $this->assertDatabaseHas('categories', [
        'name' => 'Suspension',
        'slug' => 'suspension',
        'status' => RecordStatus::ACTIVE->value,
    ], 'tenant');
});

it('creates unique slug when category name repeats', function (): void {
    authenticateCategoryUser();

    Category::query()->create([
        'name' => 'Tyres',
        'slug' => 'tyres',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $this->post(categoriesTenantRoute('categories.store'), [
        'name' => 'Tyres',
        'status' => RecordStatus::ACTIVE->value,
    ])->assertRedirect(categoriesTenantRoute('categories.index'));

    $this->assertDatabaseHas('categories', [
        'name' => 'Tyres',
        'slug' => 'tyres-2',
    ], 'tenant');
});

it('stores category with a parent', function (): void {
    authenticateCategoryUser();

    $parent = Category::query()->create([
        'name' => 'Electrical',
        'slug' => 'electrical',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $this->post(categoriesTenantRoute('categories.store'), [
        'name' => 'Batteries',
        'parent_id' => $parent->id,
        'status' => RecordStatus::ACTIVE->value,
    ])->assertRedirect(categoriesTenantRoute('categories.index'));

    $this->assertDatabaseHas('categories', [
        'name' => 'Batteries',
        'parent_id' => $parent->id,
    ], 'tenant');
});

it('validates required category fields', function (string $field): void {
    authenticateCategoryUser();

    $payload = [
        'name' => 'Body',
        'status' => RecordStatus::ACTIVE->value,
    ];

    unset($payload[$field]);

    $response = $this->from(categoriesTenantRoute('categories.index'))
        ->post(categoriesTenantRoute('categories.store'), $payload);

    $response->assertRedirect(categoriesTenantRoute('categories.index'));
    $response->assertSessionHasErrors([$field]);
})->with([
    'name' => 'name',
    'status' => 'status',
]);

it('validates category parent as existing uuid', function (): void {
    authenticateCategoryUser();

    $invalidUuidResponse = $this->from(categoriesTenantRoute('categories.index'))
        ->post(categoriesTenantRoute('categories.store'), [
            'name' => 'Lights',
            'parent_id' => 'invalid-id',
            'status' => RecordStatus::ACTIVE->value,
        ]);

    $missingParentResponse = $this->from(categoriesTenantRoute('categories.index'))
        ->post(categoriesTenantRoute('categories.store'), [
            'name' => 'Mirrors',
            'parent_id' => (string) Str::uuid(),
            'status' => RecordStatus::ACTIVE->value,
        ]);

    $invalidUuidResponse->assertSessionHasErrors(['parent_id']);
    $missingParentResponse->assertSessionHasErrors(['parent_id']);
});

it('clamps categories pagination limits', function (): void {
    authenticateCategoryUser();

    $minResponse = $this->get(categoriesTenantRoute('categories.index', ['per_page' => 1]));
    $maxResponse = $this->get(categoriesTenantRoute('categories.index', ['per_page' => 999]));

    expect($minResponse->viewData('items')->perPage())->toBe(5);
    expect($maxResponse->viewData('items')->perPage())->toBe(100);
});

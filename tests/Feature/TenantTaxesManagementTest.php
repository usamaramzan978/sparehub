<?php

declare(strict_types=1);

use App\Actions\Tenant\Tax\DeleteTaxAction;
use App\Actions\Tenant\Tax\UpdateTaxAction;
use App\Enums\BranchStatus;
use App\Enums\RecordStatus;
use App\Models\Branch;
use App\Models\Tax;
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

function taxesTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

function authenticateTaxUser(): void
{
    $branch = Branch::query()->create([
        'code' => 'MAIN',
        'name' => 'Main Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $user = User::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Tax User',
        'email' => 'tax.user+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => 'active',
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $branch->id]);
}

it('shows taxes index', function (): void {
    authenticateTaxUser();

    Tax::query()->create([
        'code' => 'GST17',
        'name' => 'GST 17%',
        'rate' => 17,
        'is_inclusive' => false,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $response = $this->get(taxesTenantRoute('taxes.index'));

    $response->assertSuccessful();
    $response->assertSee('Taxes');
    $response->assertSee('GST 17%');
    $response->assertSee('data-ajax-table-search', false);
    $response->assertSee('taxes-search-form');
    $response->assertSee('taxes-search-loading');
});

it('filters taxes by code or name', function (): void {
    authenticateTaxUser();

    Tax::query()->create([
        'code' => 'GST17',
        'name' => 'GST 17%',
        'rate' => 17,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    Tax::query()->create([
        'code' => 'VAT5',
        'name' => 'VAT 5%',
        'rate' => 5,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $byCodeResponse = $this->get(taxesTenantRoute('taxes.index', ['search' => 'GST']));
    $byNameResponse = $this->get(taxesTenantRoute('taxes.index', ['search' => 'VAT']));

    $byCodeResponse->assertSuccessful();
    $byCodeResponse->assertSee('GST 17%');
    $byCodeResponse->assertDontSee('VAT 5%');

    $byNameResponse->assertSuccessful();
    $byNameResponse->assertSee('VAT 5%');
    $byNameResponse->assertDontSee('GST 17%');
});

it('stores tax with inclusive flag', function (): void {
    authenticateTaxUser();

    $response = $this->post(taxesTenantRoute('taxes.store'), [
        'code' => 'VAT5',
        'name' => 'VAT 5%',
        'rate' => 5,
        'is_inclusive' => '1',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $response->assertRedirect(taxesTenantRoute('taxes.index'));
    $response->assertSessionHas('status', 'Created.');

    $this->assertDatabaseHas('taxes', [
        'code' => 'VAT5',
        'name' => 'VAT 5%',
        'rate' => 5,
        'is_inclusive' => 1,
        'status' => RecordStatus::ACTIVE->value,
    ], 'tenant');
});

it('validates required tax fields', function (string $field): void {
    authenticateTaxUser();

    $payload = [
        'code' => 'GST16',
        'name' => 'GST 16%',
        'rate' => 16,
        'status' => RecordStatus::ACTIVE->value,
    ];

    unset($payload[$field]);

    $response = $this->from(taxesTenantRoute('taxes.index'))
        ->post(taxesTenantRoute('taxes.store'), $payload);

    $response->assertRedirect(taxesTenantRoute('taxes.index'));
    $response->assertSessionHasErrors([$field]);
})->with([
    'code' => 'code',
    'name' => 'name',
    'rate' => 'rate',
    'status' => 'status',
]);

it('validates tax rate bounds', function (): void {
    authenticateTaxUser();

    $belowMin = $this->from(taxesTenantRoute('taxes.index'))
        ->post(taxesTenantRoute('taxes.store'), [
            'code' => 'NEG',
            'name' => 'Negative Tax',
            'rate' => -1,
            'status' => RecordStatus::ACTIVE->value,
        ]);

    $aboveMax = $this->from(taxesTenantRoute('taxes.index'))
        ->post(taxesTenantRoute('taxes.store'), [
            'code' => 'HIGH',
            'name' => 'High Tax',
            'rate' => 101,
            'status' => RecordStatus::ACTIVE->value,
        ]);

    $belowMin->assertSessionHasErrors(['rate']);
    $aboveMax->assertSessionHasErrors(['rate']);
});

it('validates unique tax code', function (): void {
    authenticateTaxUser();

    Tax::query()->create([
        'code' => 'GST18',
        'name' => 'GST 18%',
        'rate' => 18,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $response = $this->from(taxesTenantRoute('taxes.index'))
        ->post(taxesTenantRoute('taxes.store'), [
            'code' => 'GST18',
            'name' => 'Duplicate GST',
            'rate' => 18,
            'status' => RecordStatus::ACTIVE->value,
        ]);

    $response->assertRedirect(taxesTenantRoute('taxes.index'));
    $response->assertSessionHasErrors(['code' => 'This tax code already exists.']);
});

it('clamps taxes pagination limits', function (): void {
    authenticateTaxUser();

    $minResponse = $this->get(taxesTenantRoute('taxes.index', ['per_page' => 1]));
    $maxResponse = $this->get(taxesTenantRoute('taxes.index', ['per_page' => 999]));

    expect($minResponse->viewData('items')->perPage())->toBe(5);
    expect($maxResponse->viewData('items')->perPage())->toBe(100);
});

it('updates tax via action', function (): void {
    authenticateTaxUser();

    $tax = Tax::query()->create([
        'code' => 'TAX10',
        'name' => 'Tax 10%',
        'rate' => 10,
        'is_inclusive' => false,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $updated = (new UpdateTaxAction())->handle($tax, [
        'code' => 'TAX11',
        'name' => 'Tax 11%',
        'rate' => 11,
        'is_inclusive' => true,
        'status' => RecordStatus::INACTIVE->value,
    ]);

    expect($updated)->toBeTrue();

    $this->assertDatabaseHas('taxes', [
        'id' => $tax->id,
        'code' => 'TAX11',
        'name' => 'Tax 11%',
        'rate' => 11,
        'is_inclusive' => 1,
        'status' => RecordStatus::INACTIVE->value,
    ], 'tenant');
});

it('deletes tax via action', function (): void {
    authenticateTaxUser();

    $tax = Tax::query()->create([
        'code' => 'DEL5',
        'name' => 'Delete Tax 5%',
        'rate' => 5,
        'is_inclusive' => false,
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $deleted = (new DeleteTaxAction())->handle($tax);

    expect($deleted)->toBeTrue();
    $this->assertDatabaseMissing('taxes', ['id' => $tax->id], 'tenant');
});

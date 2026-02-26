<?php

declare(strict_types=1);

use App\Actions\Tenant\SaleHold\DeleteSaleHoldAction;
use App\Actions\Tenant\SaleHold\EnsureSaleHoldInBranchAction;
use App\Enums\BranchStatus;
use App\Http\Controllers\Tenant\SaleHoldController;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\SaleHold;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Stancl\Tenancy\Middleware\InitializeTenancyByPath;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

function saleHoldsTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

/**
 * @return array{current: Branch, secondary: Branch, user: User, customer: Customer}
 */
function authenticateSaleHoldsUser(): array
{
    $currentBranch = Branch::query()->create([
        'code' => 'MAIN',
        'name' => 'Main Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $secondaryBranch = Branch::query()->create([
        'code' => 'ALT',
        'name' => 'Alt Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $user = User::query()->create([
        'branch_id' => $currentBranch->id,
        'name' => 'SaleHold User',
        'email' => 'sale.hold.user+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => 'active',
    ]);

    $customer = Customer::query()->withoutGlobalScopes()->create([
        'branch_id' => $currentBranch->id,
        'code' => 'SH-CUST-1',
        'name' => 'Sale Hold Customer',
        'status' => 'active',
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $currentBranch->id]);

    return [
        'current' => $currentBranch,
        'secondary' => $secondaryBranch,
        'user' => $user,
        'customer' => $customer,
    ];
}

it('shows sale holds index for current branch only', function (): void {
    $fixture = authenticateSaleHoldsUser();

    SaleHold::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'customer_id' => $fixture['customer']->id,
        'created_by' => $fixture['user']->id,
        'hold_no' => 'HOLD-MAIN-1',
        'payload' => ['items' => [['name' => 'Engine Oil', 'qty' => 1]]],
    ]);

    SaleHold::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['secondary']->id,
        'created_by' => $fixture['user']->id,
        'hold_no' => 'HOLD-ALT-1',
        'payload' => ['items' => [['name' => 'Alt', 'qty' => 2]]],
    ]);

    $response = $this->get(saleHoldsTenantRoute('sale-holds.index'));

    $response->assertSuccessful();
    $response->assertSee('data-ajax-table-search', false);
    $response->assertSee('data-ajax-sort-link', false);
    $response->assertSee('id="sale-holds-search-form"', false);

    expect($response->viewData('items')->total())->toBe(1);
    expect($response->viewData('items')->items()[0]->hold_no)->toBe('HOLD-MAIN-1');
});

it('sorts sale holds by hold number ascending and descending', function (): void {
    $fixture = authenticateSaleHoldsUser();

    SaleHold::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'created_by' => $fixture['user']->id,
        'hold_no' => 'HOLD-SORT-A',
        'payload' => ['items' => [['name' => 'A']]],
    ]);

    SaleHold::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'created_by' => $fixture['user']->id,
        'hold_no' => 'HOLD-SORT-Z',
        'payload' => ['items' => [['name' => 'Z']]],
    ]);

    $ascending = $this->get(saleHoldsTenantRoute('sale-holds.index', [
        'sort_by' => 'hold_no',
        'sort_direction' => 'asc',
    ]));

    $descending = $this->get(saleHoldsTenantRoute('sale-holds.index', [
        'sort_by' => 'hold_no',
        'sort_direction' => 'desc',
    ]));

    $ascendingHoldNumbers = $ascending->viewData('items')->pluck('hold_no')->values()->all();
    $descendingHoldNumbers = $descending->viewData('items')->pluck('hold_no')->values()->all();

    expect(array_search('HOLD-SORT-A', $ascendingHoldNumbers, true))->toBeLessThan(array_search('HOLD-SORT-Z', $ascendingHoldNumbers, true));
    expect(array_search('HOLD-SORT-A', $descendingHoldNumbers, true))->toBeGreaterThan(array_search('HOLD-SORT-Z', $descendingHoldNumbers, true));
});

it('searches sale holds by hold number and customer', function (): void {
    $fixture = authenticateSaleHoldsUser();

    SaleHold::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'customer_id' => $fixture['customer']->id,
        'created_by' => $fixture['user']->id,
        'hold_no' => 'HOLD-SEARCH-1',
        'payload' => ['items' => [['name' => 'Searchable']]],
    ]);

    SaleHold::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'created_by' => $fixture['user']->id,
        'hold_no' => 'HOLD-OTHER-1',
        'payload' => ['items' => [['name' => 'Other']]],
    ]);

    $byNumber = $this->get(saleHoldsTenantRoute('sale-holds.index', ['search' => 'SEARCH']));
    $byCustomer = $this->get(saleHoldsTenantRoute('sale-holds.index', ['search' => 'Sale Hold Customer']));

    expect($byNumber->viewData('items')->total())->toBe(1);
    expect($byCustomer->viewData('items')->total())->toBe(1);
});

it('clamps sale holds pagination limits', function (): void {
    authenticateSaleHoldsUser();

    $minResponse = $this->get(saleHoldsTenantRoute('sale-holds.index', ['per_page' => 1]));
    $maxResponse = $this->get(saleHoldsTenantRoute('sale-holds.index', ['per_page' => 999]));

    expect($minResponse->viewData('items')->perPage())->toBe(5);
    expect($maxResponse->viewData('items')->perPage())->toBe(100);
});

it('stores sale hold with decoded payload and creator', function (): void {
    $fixture = authenticateSaleHoldsUser();

    $response = $this->post(saleHoldsTenantRoute('sale-holds.store'), [
        'customer_id' => $fixture['customer']->id,
        'hold_no' => 'HOLD-STORE-1',
        'payload' => json_encode(['items' => [['type' => 'product', 'qty' => 2]]], JSON_THROW_ON_ERROR),
        'expires_at' => now()->addDay()->toDateTimeString(),
    ]);

    $response->assertRedirect(saleHoldsTenantRoute('sale-holds.index'));

    $hold = SaleHold::query()->where('hold_no', 'HOLD-STORE-1')->firstOrFail();
    expect($hold->created_by)->toBe($fixture['user']->id);
    expect($hold->payload)->toBe(['items' => [['type' => 'product', 'qty' => 2]]]);
});

it('validates payload must be valid json', function (): void {
    authenticateSaleHoldsUser();

    $response = $this->from(saleHoldsTenantRoute('sale-holds.index'))
        ->post(saleHoldsTenantRoute('sale-holds.store'), [
            'hold_no' => 'HOLD-BAD-1',
            'payload' => '{bad-json}',
        ]);

    $response->assertRedirect(saleHoldsTenantRoute('sale-holds.index'));
    $response->assertSessionHasErrors(['payload']);
});

it('deletes sale hold', function (): void {
    $fixture = authenticateSaleHoldsUser();

    $hold = SaleHold::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'created_by' => $fixture['user']->id,
        'hold_no' => 'HOLD-DEL-1',
        'payload' => ['items' => [['name' => 'Delete']]],
    ]);

    session()->put('tenant.current_branch_id', $fixture['current']->id);
    $response = (new SaleHoldController())->destroy(
        $hold,
        new DeleteSaleHoldAction(),
        new EnsureSaleHoldInBranchAction()
    );

    expect($response->getTargetUrl())->toBe(saleHoldsTenantRoute('sale-holds.index'));
    expect($response->getSession()->get('status'))->toBe('Deleted.');
    $this->assertDatabaseMissing('sale_holds', ['id' => $hold->id], 'tenant');
});

it('throws not found when showing hold outside current branch', function (): void {
    $fixture = authenticateSaleHoldsUser();

    $foreignHold = SaleHold::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['secondary']->id,
        'created_by' => $fixture['user']->id,
        'hold_no' => 'HOLD-ALT-404',
        'payload' => ['items' => [['name' => 'Alt']]],
    ]);

    $this->expectException(NotFoundHttpException::class);
    (new SaleHoldController())->show($foreignHold, new EnsureSaleHoldInBranchAction());
});

<?php

declare(strict_types=1);

use App\Actions\Tenant\User\DeleteUserAction;
use App\Enums\BranchStatus;
use App\Enums\InvoiceType;
use App\Enums\RoleName;
use App\Enums\SaleStatus;
use App\Enums\ServiceCatalogType;
use App\Enums\UserDeletionResult;
use App\Enums\UserStatus;
use App\Http\Controllers\Tenant\UserController;
use App\Models\Branch;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\UserCommissionRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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

function usersTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

/**
 * @return array{current: Branch, secondary: Branch, user: User}
 */
function authenticateUsersModuleUser(): array
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
        'name' => 'Module Admin',
        'email' => 'module.admin+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $currentBranch->id]);

    return [
        'current' => $currentBranch,
        'secondary' => $secondaryBranch,
        'user' => $user,
    ];
}

it('shows users index scoped to current branch', function (): void {
    $fixture = authenticateUsersModuleUser();

    User::query()->create([
        'branch_id' => $fixture['current']->id,
        'name' => 'Current Branch User',
        'email' => 'current+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    User::query()->create([
        'branch_id' => $fixture['secondary']->id,
        'name' => 'Secondary Branch User',
        'email' => 'secondary+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    $response = $this->get(usersTenantRoute('users.index'));

    $response->assertSuccessful();
    $response->assertSee('data-ajax-sort-link', false);

    $items = $response->viewData('items');
    expect($items->total())->toBe(2);
});

it('sorts users by name ascending and descending', function (): void {
    $fixture = authenticateUsersModuleUser();

    User::query()->create([
        'branch_id' => $fixture['current']->id,
        'name' => 'User Sort A',
        'email' => 'user.sort.a+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    User::query()->create([
        'branch_id' => $fixture['current']->id,
        'name' => 'User Sort Z',
        'email' => 'user.sort.z+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    $ascending = $this->get(usersTenantRoute('users.index', [
        'sort_by' => 'name',
        'sort_direction' => 'asc',
    ]));

    $descending = $this->get(usersTenantRoute('users.index', [
        'sort_by' => 'name',
        'sort_direction' => 'desc',
    ]));

    $ascendingNames = $ascending->viewData('items')->pluck('name')->values()->all();
    $descendingNames = $descending->viewData('items')->pluck('name')->values()->all();

    expect(array_search('User Sort A', $ascendingNames, true))->toBeLessThan(array_search('User Sort Z', $ascendingNames, true));
    expect(array_search('User Sort A', $descendingNames, true))->toBeGreaterThan(array_search('User Sort Z', $descendingNames, true));
});

it('clears active sort query on third click from descending state', function (): void {
    $fixture = authenticateUsersModuleUser();

    User::query()->create([
        'branch_id' => $fixture['current']->id,
        'name' => 'User Reset',
        'email' => 'user.reset+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    $response = $this->get(usersTenantRoute('users.index', [
        'search' => 'User Reset',
        'sort_by' => 'name',
        'sort_direction' => 'desc',
    ]));

    $document = new DOMDocument();
    @$document->loadHTML((string) $response->getContent());
    $xpath = new DOMXPath($document);

    $nodes = $xpath->query('//a[@data-sort-column="name"]');
    $nameSortLinkHref = $nodes !== false && $nodes->length > 0 ? $nodes->item(0)?->getAttribute('href') : null;

    expect($nameSortLinkHref)->not->toBeNull();

    parse_str((string) parse_url((string) $nameSortLinkHref, PHP_URL_QUERY), $queryParams);

    expect($queryParams)->toHaveKey('search', 'User Reset');
    expect($queryParams)->not->toHaveKey('sort_by');
    expect($queryParams)->not->toHaveKey('sort_direction');
});

it('filters users by search and status', function (): void {
    $fixture = authenticateUsersModuleUser();

    User::query()->create([
        'branch_id' => $fixture['current']->id,
        'name' => 'Searchable User',
        'email' => 'searchable+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::SUSPENDED->value,
    ]);

    User::query()->create([
        'branch_id' => $fixture['current']->id,
        'name' => 'Other User',
        'email' => 'other+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    $searchResponse = $this->get(usersTenantRoute('users.index', ['search' => 'Searchable']));
    $statusResponse = $this->get(usersTenantRoute('users.index', ['status' => UserStatus::SUSPENDED->value]));

    expect($searchResponse->viewData('items')->total())->toBe(1);
    expect($statusResponse->viewData('items')->total())->toBe(1);
});

it('clamps users pagination limits', function (): void {
    authenticateUsersModuleUser();

    $minResponse = $this->get(usersTenantRoute('users.index', ['per_page' => 1]));
    $maxResponse = $this->get(usersTenantRoute('users.index', ['per_page' => 999]));

    expect($minResponse->viewData('items')->perPage())->toBe(5);
    expect($maxResponse->viewData('items')->perPage())->toBe(100);
});

it('validates user create request payload', function (): void {
    authenticateUsersModuleUser();

    $response = $this->from(usersTenantRoute('users.create'))
        ->post(usersTenantRoute('users.store'), [
            'name' => '',
            'email' => 'bad-email',
            'password' => '123',
            'password_confirmation' => '456',
            'status' => '',
        ]);

    $response->assertRedirect(usersTenantRoute('users.create'));
    $response->assertSessionHasErrors(['name', 'email', 'password', 'status']);
});

it('stores user cnic and image', function (): void {
    $fixture = authenticateUsersModuleUser();
    Storage::fake('public');
    $image = UploadedFile::fake()->image('mechanic.jpg');

    $response = $this
        ->withSession(['tenant.current_branch_id' => $fixture['current']->id])
        ->post(usersTenantRoute('users.store'), [
            'name' => 'Mechanic With Image',
            'email' => 'mechanic.image+'.uniqid('', true).'@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '+92-300-9999999',
            'cnic' => '35202-1234567-1',
            'status' => UserStatus::ACTIVE->value,
            'image' => $image,
        ]);

    $response->assertRedirect(usersTenantRoute('users.index'));

    $createdUser = User::query()
        ->where('branch_id', $fixture['current']->id)
        ->where('name', 'Mechanic With Image')
        ->first();

    expect($createdUser)->not->toBeNull();
    expect($createdUser?->cnic)->toBe('35202-1234567-1');
    expect($createdUser?->image_path)->not->toBeNull();
    Storage::disk('public')->assertExists((string) $createdUser?->image_path);
});

it('replaces user image on update', function (): void {
    $fixture = authenticateUsersModuleUser();
    Storage::fake('public');

    $existingUser = User::query()->create([
        'branch_id' => $fixture['current']->id,
        'name' => 'Mechanic Replace Image',
        'email' => 'mechanic.replace+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    $oldPath = UploadedFile::fake()->image('old.jpg')->store('users', 'public');
    $existingUser->update(['image_path' => $oldPath]);
    $newImage = UploadedFile::fake()->image('new.jpg');
    $updated = app(App\Actions\Tenant\User\UpdateUserAction::class)->handle(
        $existingUser,
        [
            'name' => 'Mechanic Replace Image',
            'email' => $existingUser->email,
            'phone' => '',
            'cnic' => '35202-7654321-1',
            'status' => UserStatus::ACTIVE->value,
            'image' => $newImage,
        ],
        $fixture['current']->id,
        app(App\Actions\Tenant\User\SyncUserCommissionRulesAction::class),
    );

    $existingUser->refresh();

    expect($updated)->toBeTrue();
    expect($existingUser->cnic)->toBe('35202-7654321-1');
    expect($existingUser->image_path)->not->toBeNull();
    expect($existingUser->image_path)->not->toBe($oldPath);
    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists((string) $existingUser->image_path);
});

it('prevents creating more users than tenant max limit', function (): void {
    $fixture = authenticateUsersModuleUser();

    Config::set('tenancy.limits.max_users', 2);

    User::query()->create([
        'branch_id' => $fixture['current']->id,
        'name' => 'Second User',
        'email' => 'second.user+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    $response = $this->from(usersTenantRoute('users.create'))
        ->post(usersTenantRoute('users.store'), [
            'name' => 'Blocked User',
            'email' => 'blocked.user+'.uniqid('', true).'@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'status' => UserStatus::ACTIVE->value,
        ]);

    $response->assertRedirect(usersTenantRoute('users.create'));
    $response->assertSessionHasErrors(['email']);
    expect(User::query()->count())->toBe(2);
});

it('throws not found when showing user outside current branch', function (): void {
    $fixture = authenticateUsersModuleUser();

    $foreignUser = User::query()->create([
        'branch_id' => $fixture['secondary']->id,
        'name' => 'Foreign User',
        'email' => 'foreign+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    $this->expectException(NotFoundHttpException::class);
    (new UserController())->show($foreignUser);
});

it('prevents deleting the last tenant owner user', function (): void {
    $fixture = authenticateUsersModuleUser();

    Role::query()->firstOrCreate([
        'name' => RoleName::TENANT_OWNER->value,
        'guard_name' => 'user',
    ]);

    $fixture['user']->assignRole(RoleName::TENANT_OWNER->value);

    $result = app(DeleteUserAction::class)->handle($fixture['user']);

    expect($result)->toBe(UserDeletionResult::LastTenantOwner);
    expect(User::query()->find($fixture['user']->id))->not->toBeNull();
});

it('allows deleting a tenant owner user when another tenant owner remains', function (): void {
    $fixture = authenticateUsersModuleUser();

    Role::query()->firstOrCreate([
        'name' => RoleName::TENANT_OWNER->value,
        'guard_name' => 'user',
    ]);

    $fixture['user']->assignRole(RoleName::TENANT_OWNER->value);

    $secondOwner = User::query()->create([
        'branch_id' => $fixture['current']->id,
        'name' => 'Second Owner',
        'email' => 'second.owner+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);
    $secondOwner->assignRole(RoleName::TENANT_OWNER->value);

    $result = app(DeleteUserAction::class)->handle($secondOwner);

    expect($result)->toBe(UserDeletionResult::Deleted);
    expect(User::query()->find($secondOwner->id))->toBeNull();
});

it('stores user commission rules via sync action', function (): void {
    $fixture = authenticateUsersModuleUser();
    $createdUser = User::query()->create([
        'branch_id' => $fixture['current']->id,
        'name' => 'Mechanic One',
        'email' => 'mechanic.one+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    $oilLabourService = ServiceCatalog::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'code' => 'LAB-OIL',
        'name' => 'Oil Labour',
        'type' => ServiceCatalogType::Labour->value,
        'base_price' => 0,
        'status' => 'active',
    ]);

    $alignmentLabourService = ServiceCatalog::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'code' => 'LAB-ALIGN',
        'name' => 'Alignment Labour',
        'type' => ServiceCatalogType::Labour->value,
        'base_price' => 0,
        'status' => 'active',
    ]);

    app(App\Actions\Tenant\User\SyncUserCommissionRulesAction::class)->handle($createdUser, [
        [
            'service_catalog_id' => $oilLabourService->id,
            'total_amount' => 1000,
            'commission_type' => 'percentage',
            'commission_value' => 10,
        ],
        [
            'service_catalog_id' => $alignmentLabourService->id,
            'total_amount' => 0,
            'commission_type' => 'fixed',
            'commission_value' => 250,
        ],
    ]);

    expect($createdUser->commissionRules()->count())->toBe(2);

    $this->assertDatabaseHas('user_commission_rules', [
        'user_id' => $createdUser->id,
        'service_catalog_id' => $oilLabourService->id,
        'payable_amount' => 100,
    ], 'tenant');

    $this->assertDatabaseHas('user_commission_rules', [
        'user_id' => $createdUser->id,
        'service_catalog_id' => $alignmentLabourService->id,
        'payable_amount' => 250,
    ], 'tenant');
});

it('shows user commission summary and service payable details', function (): void {
    $fixture = authenticateUsersModuleUser();
    $mechanic = User::query()->create([
        'branch_id' => $fixture['current']->id,
        'name' => 'Mechanic Detail',
        'email' => 'mechanic.detail+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    $brakeLabourService = ServiceCatalog::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'code' => 'LAB-BRAKE',
        'name' => 'Brake Labour',
        'type' => ServiceCatalogType::Labour->value,
        'base_price' => 0,
        'status' => 'active',
    ]);

    UserCommissionRule::query()->create([
        'user_id' => $mechanic->id,
        'service_catalog_id' => $brakeLabourService->id,
        'total_amount' => 500,
        'commission_type' => 'percentage',
        'commission_value' => 20,
        'payable_amount' => 100,
        'sort_order' => 0,
    ]);

    $sale = Sale::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'created_by' => $fixture['user']->id,
        'invoice_no' => 'INV-USER-1',
        'invoice_date' => now()->toDateString(),
        'status' => SaleStatus::POSTED->value,
        'invoice_type' => InvoiceType::SERVICE->value,
        'grand_total' => 500,
    ]);

    SaleItem::query()->withoutGlobalScopes()->create([
        'sale_id' => $sale->id,
        'branch_id' => $fixture['current']->id,
        'mechanic_id' => $mechanic->id,
        'line_type' => 'service',
        'description' => 'Brake Service Labour',
        'qty' => 1,
        'unit_price' => 500,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'mechanic_charge' => 120,
        'line_total' => 500,
    ]);

    session()->put('tenant.current_branch_id', $fixture['current']->id);
    $response = (new UserController())->show($mechanic);

    expect($response->name())->toBe('tenants.users.show');
    expect($response->getData()['commissionSummary']['rules_count'])->toBe(1);
    expect($response->getData()['commissionSummary']['service_entries_count'])->toBe(1);
    expect($response->getData()['servicePayables']->count())->toBe(1);
});

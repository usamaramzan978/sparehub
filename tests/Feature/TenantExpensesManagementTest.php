<?php

declare(strict_types=1);

use App\Enums\BranchStatus;
use App\Enums\PaymentMethodType;
use App\Enums\UserStatus;
use App\Models\Branch;
use App\Models\Expense;
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

function expensesTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

/**
 * @return array{current: Branch, secondary: Branch, user: User}
 */
function authenticateExpenseUser(): array
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
        'name' => 'Expense User',
        'email' => 'expense.user+'.uniqid('', true).'@example.test',
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

it('shows expenses index for current branch only', function (): void {
    $fixture = authenticateExpenseUser();

    Expense::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'created_by' => $fixture['user']->id,
        'title' => 'Lunch for mechanics',
        'category' => 'Lunch',
        'amount' => 20,
        'payment_method' => PaymentMethodType::CASH->value,
        'expense_date' => now()->toDateString(),
    ]);

    Expense::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['secondary']->id,
        'created_by' => $fixture['user']->id,
        'title' => 'Should not show',
        'amount' => 50,
        'payment_method' => PaymentMethodType::BANK->value,
        'expense_date' => now()->toDateString(),
    ]);

    $response = $this->get(expensesTenantRoute('expenses.index'));

    $response->assertSuccessful();
    expect($response->viewData('items')->total())->toBe(1);
    $response->assertSee('Lunch for mechanics');
    $response->assertDontSee('Should not show');
});

it('stores expense', function (): void {
    authenticateExpenseUser();

    $response = $this->post(expensesTenantRoute('expenses.store'), [
        'title' => 'Tea and snacks',
        'category' => 'Office',
        'amount' => 15.50,
        'payment_method' => PaymentMethodType::CASH->value,
        'expense_date' => now()->toDateString(),
        'reference_no' => 'EXP-1',
        'notes' => 'Daily staff refreshments',
    ]);

    $response->assertRedirect(expensesTenantRoute('expenses.index'));
    $response->assertSessionHas('status', 'Created.');
    $this->assertDatabaseHas('expenses', [
        'title' => 'Tea and snacks',
        'category' => 'Office',
        'amount' => 15.50,
        'reference_no' => 'EXP-1',
    ], 'tenant');
});

it('updates expense', function (): void {
    $fixture = authenticateExpenseUser();

    $expense = Expense::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'created_by' => $fixture['user']->id,
        'title' => 'Original title',
        'amount' => 30,
        'payment_method' => PaymentMethodType::CASH->value,
        'expense_date' => now()->toDateString(),
    ]);
    expect($expense->id)->not->toBeNull();

    $response = $this->put(expensesTenantRoute('expenses.update', ['expense' => $expense]), [
        'title' => 'Updated title',
        'category' => 'Transport',
        'amount' => 45,
        'payment_method' => PaymentMethodType::BANK->value,
        'expense_date' => now()->toDateString(),
    ]);

    $response->assertRedirect(expensesTenantRoute('expenses.index'));
    $response->assertSessionHas('status', 'Updated.');
    $this->assertDatabaseHas('expenses', [
        'id' => $expense->id,
        'title' => 'Updated title',
        'category' => 'Transport',
        'amount' => 45,
        'payment_method' => PaymentMethodType::BANK->value,
    ], 'tenant');
});

it('deletes expense', function (): void {
    $fixture = authenticateExpenseUser();

    $expense = Expense::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'created_by' => $fixture['user']->id,
        'title' => 'Delete me',
        'amount' => 20,
        'payment_method' => PaymentMethodType::CASH->value,
        'expense_date' => now()->toDateString(),
    ]);
    expect($expense->id)->not->toBeNull();

    $response = $this->delete(expensesTenantRoute('expenses.destroy', ['expense' => $expense]));

    $response->assertRedirect(expensesTenantRoute('expenses.index'));
    $response->assertSessionHas('status', 'Deleted.');
    $this->assertDatabaseMissing('expenses', ['id' => $expense->id], 'tenant');
});

it('validates required fields when storing expense', function (): void {
    authenticateExpenseUser();

    $response = $this->from(expensesTenantRoute('expenses.index'))
        ->post(expensesTenantRoute('expenses.store'), [
            'amount' => 0,
        ]);

    $response->assertRedirect(expensesTenantRoute('expenses.index'));
    $response->assertSessionHasErrors(['title', 'payment_method', 'expense_date', 'amount']);
});

it('returns not found when updating expense outside current branch', function (): void {
    $fixture = authenticateExpenseUser();

    $foreignExpense = Expense::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['secondary']->id,
        'created_by' => $fixture['user']->id,
        'title' => 'Foreign expense',
        'amount' => 20,
        'payment_method' => PaymentMethodType::CASH->value,
        'expense_date' => now()->toDateString(),
    ]);

    expect($foreignExpense->id)->not->toBeNull();

    $response = $this->put(expensesTenantRoute('expenses.update', ['expense' => $foreignExpense]), [
        'title' => 'Should fail',
        'amount' => 25,
        'payment_method' => PaymentMethodType::BANK->value,
        'expense_date' => now()->toDateString(),
    ]);

    $response->assertNotFound();
});

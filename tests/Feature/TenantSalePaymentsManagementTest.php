<?php

declare(strict_types=1);

use App\Enums\BranchStatus;
use App\Enums\InvoiceType;
use App\Enums\PaymentMethodType;
use App\Enums\SaleStatus;
use App\Http\Controllers\Tenant\SalePaymentController;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SalePayment;
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

function salePaymentsTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

/**
 * @return array{current: Branch, secondary: Branch, sale: Sale, user: User}
 */
function authenticateSalePaymentsUser(): array
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
        'name' => 'SalePayment User',
        'email' => 'sale.payment.user+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => 'active',
    ]);

    $customer = Customer::query()->withoutGlobalScopes()->create([
        'branch_id' => $currentBranch->id,
        'code' => 'SP-CUST-1',
        'name' => 'Sale Payment Customer',
        'status' => 'active',
    ]);

    $sale = Sale::query()->withoutGlobalScopes()->create([
        'branch_id' => $currentBranch->id,
        'customer_id' => $customer->id,
        'created_by' => $user->id,
        'invoice_no' => 'SP-INV-1',
        'invoice_date' => now()->toDateString(),
        'status' => SaleStatus::POSTED->value,
        'invoice_type' => InvoiceType::PRODUCT->value,
        'sub_total' => 500,
        'discount_total' => 0,
        'tax_total' => 0,
        'grand_total' => 500,
        'paid_total' => 0,
        'balance_due' => 500,
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $currentBranch->id]);

    return [
        'current' => $currentBranch,
        'secondary' => $secondaryBranch,
        'sale' => $sale,
        'user' => $user,
    ];
}

it('shows sale payments index for current branch only', function (): void {
    $fixture = authenticateSalePaymentsUser();

    SalePayment::query()->withoutGlobalScopes()->create([
        'sale_id' => $fixture['sale']->id,
        'branch_id' => $fixture['current']->id,
        'received_by' => $fixture['user']->id,
        'payment_method' => PaymentMethodType::CASH->value,
        'amount' => 100,
        'paid_at' => now(),
    ]);

    $foreignSale = Sale::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['secondary']->id,
        'created_by' => $fixture['user']->id,
        'invoice_no' => 'SP-INV-2',
        'invoice_date' => now()->toDateString(),
        'status' => SaleStatus::POSTED->value,
        'invoice_type' => InvoiceType::PRODUCT->value,
        'grand_total' => 200,
    ]);

    SalePayment::query()->withoutGlobalScopes()->create([
        'sale_id' => $foreignSale->id,
        'branch_id' => $fixture['secondary']->id,
        'received_by' => $fixture['user']->id,
        'payment_method' => PaymentMethodType::BANK->value,
        'amount' => 80,
        'paid_at' => now(),
    ]);

    $response = $this->get(salePaymentsTenantRoute('sale-payments.index'));

    $response->assertSuccessful();

    expect($response->viewData('items')->total())->toBe(1);
});

it('clamps sale payments pagination limits', function (): void {
    authenticateSalePaymentsUser();

    $minResponse = $this->get(salePaymentsTenantRoute('sale-payments.index', ['per_page' => 1]));
    $maxResponse = $this->get(salePaymentsTenantRoute('sale-payments.index', ['per_page' => 999]));

    expect($minResponse->viewData('items')->perPage())->toBe(5);
    expect($maxResponse->viewData('items')->perPage())->toBe(100);
});

it('stores sale payment and recalculates sale paid and balance totals', function (): void {
    $fixture = authenticateSalePaymentsUser();

    $response = $this->post(salePaymentsTenantRoute('sale-payments.store'), [
        'sale_id' => $fixture['sale']->id,
        'received_by' => $fixture['user']->id,
        'payment_method' => PaymentMethodType::CASH->value,
        'amount' => 125,
        'paid_at' => now()->toDateTimeString(),
        'notes' => 'Initial payment',
    ]);

    $response->assertRedirect(salePaymentsTenantRoute('sale-payments.index'));

    $fixture['sale']->refresh();
    expect((float) $fixture['sale']->paid_total)->toBe(125.0);
    expect((float) $fixture['sale']->balance_due)->toBe(375.0);
});

it('validates payment amount must be greater than zero', function (): void {
    $fixture = authenticateSalePaymentsUser();

    $response = $this->from(salePaymentsTenantRoute('sale-payments.create'))
        ->post(salePaymentsTenantRoute('sale-payments.store'), [
            'sale_id' => $fixture['sale']->id,
            'payment_method' => PaymentMethodType::CASH->value,
            'amount' => 0,
            'paid_at' => now()->toDateTimeString(),
        ]);

    $response->assertRedirect(salePaymentsTenantRoute('sale-payments.create'));
    $response->assertSessionHasErrors(['amount']);
});

it('deletes sale payment and recalculates sale totals', function (): void {
    $fixture = authenticateSalePaymentsUser();

    $payment = SalePayment::query()->withoutGlobalScopes()->create([
        'sale_id' => $fixture['sale']->id,
        'branch_id' => $fixture['current']->id,
        'received_by' => $fixture['user']->id,
        'payment_method' => PaymentMethodType::CASH->value,
        'amount' => 200,
        'paid_at' => now(),
    ]);

    $fixture['sale']->update([
        'paid_total' => 200,
        'balance_due' => 300,
    ]);

    session()->put('tenant.current_branch_id', $fixture['current']->id);
    $response = (new SalePaymentController())->destroy($payment);

    expect($response->getTargetUrl())->toBe(salePaymentsTenantRoute('sale-payments.index'));
    expect($response->getSession()->get('status'))->toBe('Deleted.');
    $this->assertDatabaseMissing('sale_payments', ['id' => $payment->id], 'tenant');

    $fixture['sale']->refresh();
    expect((float) $fixture['sale']->paid_total)->toBe(0.0);
    expect((float) $fixture['sale']->balance_due)->toBe(500.0);
});

it('throws not found when showing payment outside current branch', function (): void {
    $fixture = authenticateSalePaymentsUser();

    $foreignSale = Sale::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['secondary']->id,
        'created_by' => $fixture['user']->id,
        'invoice_no' => 'SP-INV-3',
        'invoice_date' => now()->toDateString(),
        'status' => SaleStatus::POSTED->value,
        'invoice_type' => InvoiceType::PRODUCT->value,
        'grand_total' => 200,
    ]);

    $foreignPayment = SalePayment::query()->withoutGlobalScopes()->create([
        'sale_id' => $foreignSale->id,
        'branch_id' => $fixture['secondary']->id,
        'received_by' => $fixture['user']->id,
        'payment_method' => PaymentMethodType::CASH->value,
        'amount' => 50,
        'paid_at' => now(),
    ]);

    $this->expectException(NotFoundHttpException::class);
    (new SalePaymentController())->show($foreignPayment);
});

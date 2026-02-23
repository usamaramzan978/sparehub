<?php

declare(strict_types=1);

use App\Actions\Tenant\VendorPayment\DeleteVendorPaymentAction;
use App\Actions\Tenant\VendorPayment\EnsureVendorPaymentInBranchAction;
use App\Enums\BranchStatus;
use App\Enums\PaymentMethodType;
use App\Enums\PurchaseStatus;
use App\Enums\RecordStatus;
use App\Http\Controllers\Tenant\VendorPaymentController;
use App\Models\Branch;
use App\Models\Purchase;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorPayment;
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

function vendorPaymentsTenantRoute(string $name, array $parameters = []): string
{
    return route('tenant.'.$name, ['tenant' => 'test-tenant-id', ...$parameters]);
}

/**
 * @return array{current: Branch, secondary: Branch, purchase: Purchase, vendor: Vendor, user: User}
 */
function authenticateVendorPaymentsUser(): array
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
        'name' => 'VendorPayment User',
        'email' => 'vendor.payment.user+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => 'active',
    ]);

    $vendor = Vendor::query()->withoutGlobalScopes()->create([
        'branch_id' => $currentBranch->id,
        'code' => 'VEN-VP-1',
        'name' => 'Payment Vendor',
        'status' => RecordStatus::ACTIVE->value,
    ]);

    $purchase = Purchase::query()->withoutGlobalScopes()->create([
        'branch_id' => $currentBranch->id,
        'vendor_id' => $vendor->id,
        'created_by' => $user->id,
        'purchase_no' => 'VP-PUR-1',
        'purchase_date' => now()->toDateString(),
        'status' => PurchaseStatus::POSTED->value,
        'grand_total' => 500,
        'paid_total' => 0,
        'balance_due' => 500,
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $currentBranch->id]);

    return [
        'current' => $currentBranch,
        'secondary' => $secondaryBranch,
        'purchase' => $purchase,
        'vendor' => $vendor,
        'user' => $user,
    ];
}

it('shows vendor payments index for current branch only', function (): void {
    $fixture = authenticateVendorPaymentsUser();

    VendorPayment::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'vendor_id' => $fixture['vendor']->id,
        'purchase_id' => $fixture['purchase']->id,
        'created_by' => $fixture['user']->id,
        'payment_no' => 'VP-MAIN-1',
        'payment_method' => PaymentMethodType::CASH->value,
        'amount' => 100,
        'paid_at' => now(),
    ]);

    VendorPayment::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['secondary']->id,
        'vendor_id' => $fixture['vendor']->id,
        'created_by' => $fixture['user']->id,
        'payment_no' => 'VP-ALT-1',
        'payment_method' => PaymentMethodType::BANK->value,
        'amount' => 50,
        'paid_at' => now(),
    ]);

    $response = $this->get(vendorPaymentsTenantRoute('vendor-payments.index'));

    $response->assertSuccessful();

    expect($response->viewData('items')->total())->toBe(1);
});

it('clamps vendor payments pagination limits', function (): void {
    authenticateVendorPaymentsUser();

    $minResponse = $this->get(vendorPaymentsTenantRoute('vendor-payments.index', ['per_page' => 1]));
    $maxResponse = $this->get(vendorPaymentsTenantRoute('vendor-payments.index', ['per_page' => 999]));

    expect($minResponse->viewData('items')->perPage())->toBe(5);
    expect($maxResponse->viewData('items')->perPage())->toBe(100);
});

it('stores vendor payment and recalculates purchase totals', function (): void {
    $fixture = authenticateVendorPaymentsUser();

    $response = $this->post(vendorPaymentsTenantRoute('vendor-payments.store'), [
        'vendor_id' => $fixture['vendor']->id,
        'purchase_id' => $fixture['purchase']->id,
        'payment_no' => 'VP-STORE-1',
        'payment_method' => PaymentMethodType::CASH->value,
        'amount' => 150,
        'paid_at' => now()->toDateTimeString(),
    ]);

    $response->assertRedirect(vendorPaymentsTenantRoute('vendor-payments.index'));

    $fixture['purchase']->refresh();
    expect((float) $fixture['purchase']->paid_total)->toBe(150.0);
    expect((float) $fixture['purchase']->balance_due)->toBe(350.0);
});

it('validates amount must be greater than zero for vendor payment', function (): void {
    $fixture = authenticateVendorPaymentsUser();

    $response = $this->from(vendorPaymentsTenantRoute('vendor-payments.create'))
        ->post(vendorPaymentsTenantRoute('vendor-payments.store'), [
            'vendor_id' => $fixture['vendor']->id,
            'purchase_id' => $fixture['purchase']->id,
            'payment_no' => 'VP-INVALID-1',
            'payment_method' => PaymentMethodType::CASH->value,
            'amount' => 0,
            'paid_at' => now()->toDateTimeString(),
        ]);

    $response->assertRedirect(vendorPaymentsTenantRoute('vendor-payments.create'));
    $response->assertSessionHasErrors(['amount']);
});

it('deletes vendor payment and recalculates purchase totals', function (): void {
    $fixture = authenticateVendorPaymentsUser();

    $payment = VendorPayment::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['current']->id,
        'vendor_id' => $fixture['vendor']->id,
        'purchase_id' => $fixture['purchase']->id,
        'created_by' => $fixture['user']->id,
        'payment_no' => 'VP-DEL-1',
        'payment_method' => PaymentMethodType::BANK->value,
        'amount' => 200,
        'paid_at' => now(),
    ]);

    $fixture['purchase']->update([
        'paid_total' => 200,
        'balance_due' => 300,
    ]);

    session()->put('tenant.current_branch_id', $fixture['current']->id);
    $response = (new VendorPaymentController())->destroy(
        $payment,
        app(DeleteVendorPaymentAction::class),
        new EnsureVendorPaymentInBranchAction()
    );

    expect($response->getTargetUrl())->toBe(vendorPaymentsTenantRoute('vendor-payments.index'));
    expect($response->getSession()->get('status'))->toBe('Deleted.');
    $this->assertDatabaseMissing('vendor_payments', ['id' => $payment->id], 'tenant');

    $fixture['purchase']->refresh();
    expect((float) $fixture['purchase']->paid_total)->toBe(0.0);
    expect((float) $fixture['purchase']->balance_due)->toBe(500.0);
});

it('throws not found when showing vendor payment outside current branch', function (): void {
    $fixture = authenticateVendorPaymentsUser();

    $foreignPayment = VendorPayment::query()->withoutGlobalScopes()->create([
        'branch_id' => $fixture['secondary']->id,
        'vendor_id' => $fixture['vendor']->id,
        'created_by' => $fixture['user']->id,
        'payment_no' => 'VP-ALT-404',
        'payment_method' => PaymentMethodType::CASH->value,
        'amount' => 100,
        'paid_at' => now(),
    ]);

    $this->expectException(NotFoundHttpException::class);
    (new VendorPaymentController())->show($foreignPayment, new EnsureVendorPaymentInBranchAction());
});

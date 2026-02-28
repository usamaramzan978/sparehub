<?php

declare(strict_types=1);

use App\Enums\BranchStatus;
use App\Enums\InvoiceType;
use App\Enums\PaymentMethodType;
use App\Enums\PurchaseStatus;
use App\Enums\SaleStatus;
use App\Enums\UserStatus;
use App\Models\Branch;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeSalary;
use App\Models\Expense;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorPayment;
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

function endOfDayTenantRoute(array $parameters = []): string
{
    return route('tenant.end-of-day', ['tenant' => 'test-tenant-id', ...$parameters]);
}

function authenticateEndOfDayUser(): array
{
    $branch = Branch::query()->create([
        'code' => 'MAIN',
        'name' => 'Main Branch',
        'status' => BranchStatus::ACTIVE->value,
    ]);

    $user = User::query()->create([
        'branch_id' => $branch->id,
        'name' => 'EOD Admin',
        'email' => 'eod.admin+'.uniqid('', true).'@example.test',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    $vendor = Vendor::query()->withoutGlobalScopes()->create([
        'branch_id' => $branch->id,
        'code' => 'EOD-V-1',
        'name' => 'EOD Vendor',
        'status' => 'active',
    ]);

    $sale = Sale::query()->withoutGlobalScopes()->create([
        'branch_id' => $branch->id,
        'created_by' => $user->id,
        'invoice_no' => 'EOD-S-1',
        'invoice_date' => now()->toDateString(),
        'status' => SaleStatus::POSTED->value,
        'invoice_type' => InvoiceType::PRODUCT->value,
        'grand_total' => 400,
    ]);

    $purchase = Purchase::query()->withoutGlobalScopes()->create([
        'branch_id' => $branch->id,
        'vendor_id' => $vendor->id,
        'created_by' => $user->id,
        'purchase_no' => 'EOD-P-1',
        'purchase_date' => now()->toDateString(),
        'status' => PurchaseStatus::POSTED->value,
        'grand_total' => 300,
    ]);

    SalePayment::query()->withoutGlobalScopes()->create([
        'sale_id' => $sale->id,
        'branch_id' => $branch->id,
        'received_by' => $user->id,
        'payment_method' => PaymentMethodType::CASH->value,
        'amount' => 400,
        'paid_at' => now(),
    ]);

    VendorPayment::query()->withoutGlobalScopes()->create([
        'branch_id' => $branch->id,
        'vendor_id' => $vendor->id,
        'purchase_id' => $purchase->id,
        'created_by' => $user->id,
        'payment_no' => 'EOD-VP-1',
        'payment_method' => PaymentMethodType::BANK->value,
        'amount' => 100,
        'paid_at' => now(),
    ]);

    EmployeeAttendance::query()->withoutGlobalScopes()->create([
        'branch_id' => $branch->id,
        'user_id' => $user->id,
        'attendance_date' => now()->toDateString(),
        'check_in_at' => now()->subHours(2),
    ]);

    EmployeeSalary::query()->withoutGlobalScopes()->create([
        'branch_id' => $branch->id,
        'user_id' => $user->id,
        'salary_month' => now()->startOfMonth()->toDateString(),
        'basic_salary' => 1000,
        'net_salary' => 1000,
        'paid_at' => now(),
    ]);

    Expense::query()->withoutGlobalScopes()->create([
        'branch_id' => $branch->id,
        'created_by' => $user->id,
        'title' => 'Workshop Lunch',
        'category' => 'Food',
        'amount' => 50,
        'payment_method' => PaymentMethodType::CASH->value,
        'expense_date' => now()->toDateString(),
    ]);

    test()->actingAs($user, 'user');
    test()->withSession(['tenant.current_branch_id' => $branch->id]);

    return ['sale' => $sale, 'purchase' => $purchase];
}

it('shows end of day summary for selected date', function (): void {
    authenticateEndOfDayUser();

    $response = $this->get(endOfDayTenantRoute(['date' => now()->toDateString()]));

    $response->assertSuccessful();

    $summary = $response->viewData('summary');
    expect($summary['sales_count'])->toBe(1);
    expect($summary['purchases_count'])->toBe(1);
    expect($summary['cash_in'])->toBe(400.0);
    expect($summary['expenses_count'])->toBe(1);
    expect($summary['expenses_total'])->toBe(50.0);
    expect($summary['cash_out'])->toBe(150.0);
    expect($summary['cash_net'])->toBe(250.0);
    expect($summary['service_entries_count'])->toBe(0);
    expect($summary['service_entries_total'])->toBe(0.0);
    expect($summary['payroll_total'])->toBe(1000.0);
});

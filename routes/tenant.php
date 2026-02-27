<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\Tenant\AuthController as TenantAuthController;
use App\Http\Controllers\Tenant\ActivityTimelineController;
use App\Http\Controllers\Tenant\BranchController;
use App\Http\Controllers\Tenant\BranchSwitchController;
use App\Http\Controllers\Tenant\BrandController;
use App\Http\Controllers\Tenant\CategoryController;
use App\Http\Controllers\Tenant\CodeGeneratorController;
use App\Http\Controllers\Tenant\CustomerController;
use App\Http\Controllers\Tenant\CustomerVehicleController;
use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\EmployeeAttendanceController;
use App\Http\Controllers\Tenant\EmployeeSalaryController;
use App\Http\Controllers\Tenant\EndOfDayController;
use App\Http\Controllers\Tenant\ExpenseController;
use App\Http\Controllers\Tenant\JobCardController;
use App\Http\Controllers\Tenant\PermissionController;
use App\Http\Controllers\Tenant\PosController;
use App\Http\Controllers\Tenant\ProductController;
use App\Http\Controllers\Tenant\ProductHistoryController;
use App\Http\Controllers\Tenant\ProfileController;
use App\Http\Controllers\Tenant\PurchaseController;
use App\Http\Controllers\Tenant\PurchaseItemController;
use App\Http\Controllers\Tenant\PurchaseReturnController;
use App\Http\Controllers\Tenant\PurchaseReturnItemController;
use App\Http\Controllers\Tenant\PurchasesTreeController;
use App\Http\Controllers\Tenant\ReportController;
use App\Http\Controllers\Tenant\RoleController;
use App\Http\Controllers\Tenant\SaleController;
use App\Http\Controllers\Tenant\SalePaymentController;
use App\Http\Controllers\Tenant\SaleReturnController;
use App\Http\Controllers\Tenant\ServiceCatalogController;
use App\Http\Controllers\Tenant\SettingController;
use App\Http\Controllers\Tenant\SupportTicketController;
use App\Http\Controllers\Tenant\TaxController;
use App\Http\Controllers\Tenant\UnitController;
use App\Http\Controllers\Tenant\UserController;
use App\Http\Controllers\Tenant\VendorController;
use App\Http\Controllers\Tenant\VendorPaymentController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByPath;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    InitializeTenancyByPath::class,
    PreventAccessFromCentralDomains::class,
])->prefix('firm/{tenant}')->name('tenant.')->group(function (): void {

    Route::get('authenticate', (new TenantAuthController())->authenticateTenant(...))
        ->middleware('throttle:10,1')
        ->name('authenticate');

    Route::middleware('guest:user')->group(function (): void {
        Route::get('login', (new TenantAuthController())->showLogin(...))->name('login')->middleware(['throttle:60,1']);
        Route::post('login', (new TenantAuthController())->login(...))->middleware('throttle:6,1')->name('auth.login.submit');
        Route::get('register', (new TenantAuthController())->showRegister(...))->name('register');
        Route::post('register', (new TenantAuthController())->register(...))->name('auth.register.submit');
        Route::get('forgot-password', (new TenantAuthController())->showForgotPassword(...))->name('forgot-password');
        Route::post('forgot-password', (new TenantAuthController())->forgotPassword(...))->middleware('throttle:6,1')->name('auth.forgot-password.submit');
        Route::get('reset-password', (new TenantAuthController())->showResetPassword(...))->name('reset-password');
        Route::post('reset-password', (new TenantAuthController())->resetPassword(...))->name('auth.reset-password.submit');
    });

    Route::middleware(['auth:user', 'tenant.branch', 'tenant.two-step'])->group(function (): void {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
        Route::get('end-of-day', EndOfDayController::class)->name('end-of-day');
        Route::get('products/history', ProductHistoryController::class)->name('products.history');
        Route::get('purchases-tree', PurchasesTreeController::class)->name('purchases-tree.index');

        Route::get('employee-attendances', [EmployeeAttendanceController::class, 'index'])->name('employee-attendances.index');
        Route::post('employee-attendances', [EmployeeAttendanceController::class, 'store'])->name('employee-attendances.store');
        Route::get('employee-salaries', [EmployeeSalaryController::class, 'index'])->name('employee-salaries.index');
        Route::post('employee-salaries', [EmployeeSalaryController::class, 'store'])->name('employee-salaries.store');

        Route::controller(TenantAuthController::class)->group(function (): void {
            Route::get('two-step', 'showTwoStep')->name('two-step');
            Route::post('two-step', 'verifyTwoStep')->name('two-step.verify');
            Route::post('logout', 'logout')->name('logout');
        });

        Route::post('branch/switch', BranchSwitchController::class)
            ->name('branch.switch');

        Route::controller(ProfileController::class)->group(function (): void {
            Route::get('profile', 'show')->name('profile.show');
            Route::get('profile/edit', 'edit')->name('profile.edit');
            Route::put('profile', 'update')->name('profile.update');
            Route::get('profile/security', 'security')->name('profile.security.show');
            Route::post('profile/security/authenticator/setup', 'setupAuthenticator')->name('profile.security.authenticator.setup');
            Route::post('profile/security/authenticator/verify', 'verifyAuthenticator')->name('profile.security.authenticator.verify');
            Route::post('profile/security/authenticator/reset', 'resetAuthenticator')->name('profile.security.authenticator.reset');
            Route::post('profile/security/backup-codes/regenerate', 'regenerateBackupCodes')->name('profile.security.backup-codes.regenerate');
        });

        Route::controller(SettingController::class)->group(function (): void {
            Route::get('settings', 'edit')->name('settings.edit');
            Route::put('settings', 'update')->name('settings.update');
        });
        Route::get('activity-timeline', ActivityTimelineController::class)->name('activity-timeline.index');

        Route::controller(PosController::class)->prefix('pos')->name('pos.')->group(function (): void {
            Route::get('/', 'index')->name('index');
            Route::get('/scan', 'scan')->name('scan');
            Route::get('/catalog', 'catalog')->name('catalog');
            Route::post('/', 'store')->name('store');
        });

        Route::controller(ReportController::class)->group(function (): void {
            Route::get('reports', 'index')->name('reports.index');
            Route::get('reports/overview', 'overview')->name('reports.overview');
            Route::get('reports/sales', 'sales')->name('reports.sales');
            Route::get('reports/purchases', 'purchases')->name('reports.purchases');
            Route::get('reports/sale-payments', 'salePayments')->name('reports.sale-payments');
            Route::get('reports/vendor-payments', 'vendorPayments')->name('reports.vendor-payments');
            Route::get('reports/receivables', 'receivables')->name('reports.receivables');
            Route::get('reports/payables', 'payables')->name('reports.payables');

            Route::get('reports/export/pdf', 'exportPdf')->name('reports.export.pdf');
            Route::get('reports/export/sales-pdf', 'exportSalesPdf')->name('reports.export.sales-pdf');
            Route::get('reports/export/purchases-pdf', 'exportPurchasesPdf')->name('reports.export.purchases-pdf');
            Route::get('reports/export/sale-payments-pdf', 'exportSalePaymentsPdf')->name('reports.export.sale-payments-pdf');
            Route::get('reports/export/vendor-payments-pdf', 'exportVendorPaymentsPdf')->name('reports.export.vendor-payments-pdf');
            Route::get('reports/export/receivables-pdf', 'exportReceivablesPdf')->name('reports.export.receivables-pdf');
            Route::get('reports/export/payables-pdf', 'exportPayablesPdf')->name('reports.export.payables-pdf');
            Route::get('reports/export/summary-pdf', 'exportSummaryPdf')->name('reports.export.summary-pdf');
        });

        Route::get('sales/{sale}/print', [SaleController::class, 'print'])
            ->name('sales.print');

        Route::resources([
            'branches' => BranchController::class,
            'users' => UserController::class,
            'roles' => RoleController::class,
            'customers' => CustomerController::class,
            'products' => ProductController::class,
        ]);

        Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');

        Route::resources([
            'service-catalog' => ServiceCatalogController::class,
            'customer-vehicles' => CustomerVehicleController::class,
            'vendors' => VendorController::class,
            'sales' => SaleController::class,
            'sale-returns' => SaleReturnController::class,
            'sale-payments' => SalePaymentController::class,
            'purchases' => PurchaseController::class,
            'purchase-items' => PurchaseItemController::class,
            'purchase-returns' => PurchaseReturnController::class,
            'purchase-return-items' => PurchaseReturnItemController::class,
            'vendor-payments' => VendorPaymentController::class,
        ]);
        Route::resource('job-cards', JobCardController::class)->parameters(['job-cards' => 'jobCard']);

        Route::resource('support-tickets', SupportTicketController::class)
            ->only(['index', 'create', 'store', 'show'])
            ->parameters(['support-tickets' => 'supportTicket']);
        Route::post('support-tickets/{supportTicket}/messages', [SupportTicketController::class, 'storeMessage'])
            ->name('support-tickets.messages.store');
        Route::get('sale-returns/invoice/{sale}/items', [SaleReturnController::class, 'invoiceItems'])
            ->name('sale-returns.invoice-items');

        Route::resource('brands', BrandController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::resource('taxes', TaxController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('units', UnitController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('expenses', ExpenseController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::get('codes', [CodeGeneratorController::class, 'index'])->name('codes.index');
        Route::post('codes', [CodeGeneratorController::class, 'store'])->name('codes.store');
        Route::delete('codes/item', [CodeGeneratorController::class, 'destroy'])->name('codes.destroy');
        Route::get('codes/render', [CodeGeneratorController::class, 'render'])->name('codes.render');
        Route::get('codes/print', [CodeGeneratorController::class, 'print'])->name('codes.print');

        Route::put('products/{product}/barcode', [CodeGeneratorController::class, 'updateBarcode'])->name('products.barcode.update');
        Route::delete('products/{product}/barcode', [CodeGeneratorController::class, 'deleteBarcode'])->name('products.barcode.delete');
        Route::put('products/{product}/qrcode', [CodeGeneratorController::class, 'updateQr'])->name('products.qrcode.update');
        Route::delete('products/{product}/qrcode', [CodeGeneratorController::class, 'deleteQr'])->name('products.qrcode.delete');
    });
});

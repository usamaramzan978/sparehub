<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\Tenant\AuthController as TenantAuthController;
use App\Http\Controllers\Tenant\BranchController;
use App\Http\Controllers\Tenant\BranchSwitchController;
use App\Http\Controllers\Tenant\BrandController;
use App\Http\Controllers\Tenant\CategoryController;
use App\Http\Controllers\Tenant\CustomerController;
use App\Http\Controllers\Tenant\CustomerVehicleController;
use App\Http\Controllers\Tenant\JobCardController;
use App\Http\Controllers\Tenant\JobCardPartController;
use App\Http\Controllers\Tenant\JobCardServiceController;
use App\Http\Controllers\Tenant\PosController;
use App\Http\Controllers\Tenant\ProductController;
use App\Http\Controllers\Tenant\ProductPriceController;
use App\Http\Controllers\Tenant\ProfileController;
use App\Http\Controllers\Tenant\PurchaseController;
use App\Http\Controllers\Tenant\PurchaseItemController;
use App\Http\Controllers\Tenant\PurchaseReturnController;
use App\Http\Controllers\Tenant\PurchaseReturnItemController;
use App\Http\Controllers\Tenant\ReportController;
use App\Http\Controllers\Tenant\RoleController;
use App\Http\Controllers\Tenant\SaleController;
use App\Http\Controllers\Tenant\SaleHoldController;
use App\Http\Controllers\Tenant\SaleItemController;
use App\Http\Controllers\Tenant\SalePaymentController;
use App\Http\Controllers\Tenant\ServiceCatalogController;
use App\Http\Controllers\Tenant\SettingController;
use App\Http\Controllers\Tenant\TaxController;
use App\Http\Controllers\Tenant\UnitController;
use App\Http\Controllers\Tenant\UserController;
use App\Http\Controllers\Tenant\VendorController;
use App\Http\Controllers\Tenant\VendorPaymentController;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByPath;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    InitializeTenancyByPath::class,
    PreventAccessFromCentralDomains::class,
])->prefix('firm/{tenant}')->name('tenant.')->group(function (): void {

    // Guest routes (login, authenticate)
    Route::middleware('guest:user')->group(function (): void {

        // This fixes Laravel redirect issue
        Route::get('login', (new TenantAuthController())->showLogin(...))->name('login');

        Route::post('login', (new TenantAuthController())->login(...))
            ->name('auth.login.submit');

        // Authenticate route for the encrypted token
        Route::get('authenticate', (new TenantAuthController())->authenticateTenant(...))
            ->name('authenticate');
    });

    // Authenticated routes
    Route::middleware(['auth:user', 'tenant.branch'])->group(function () {

        Route::view('dashboard', 'tenants.dashboard')->name('dashboard');

        // Auth / Session
        Route::controller(TenantAuthController::class)->group(function () {
            Route::get('two-step', 'showTwoStep')->name('two-step');
            Route::post('two-step', 'verifyTwoStep')->name('two-step.verify');
            Route::post('logout', 'logout')->name('logout');
        });

        // Branch Switch
        Route::post('branch/switch', [BranchSwitchController::class, 'store'])
            ->name('branch.switch');

        // Profile
        Route::controller(ProfileController::class)->group(function () {
            Route::get('profile', 'show')->name('profile.show');
            Route::get('profile/edit', 'edit')->name('profile.edit');
            Route::put('profile', 'update')->name('profile.update');
        });

        // Settings
        Route::controller(SettingController::class)->group(function () {
            Route::get('settings', 'edit')->name('settings.edit');
            Route::put('settings', 'update')->name('settings.update');
        });

        // POS
        Route::controller(PosController::class)->prefix('pos')->name('pos.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/scan', 'scan')->name('scan');
            Route::get('/catalog', 'catalog')->name('catalog');
            Route::post('/', 'store')->name('store');
        });

        // Reports
        Route::controller(ReportController::class)->group(function () {
            Route::get('reports', 'index')->name('reports.index');
            Route::get('reports/export/pdf', 'exportPdf')->name('reports.export.pdf');
            Route::get('reports/export/summary-pdf', 'exportSummaryPdf')->name('reports.export.summary-pdf');
        });

        // Sales Print
        Route::get('sales/{sale}/print', [SaleController::class, 'print'])
            ->name('sales.print');

        // Full Resources
        Route::resources([
            'branches'  => BranchController::class,
            'users'     => UserController::class,
            'roles'     => RoleController::class,
            'customers' => CustomerController::class,
            'products'  => ProductController::class,
        ]);

        // Business Resources
        Route::resources([
            'product-prices'          => ProductPriceController::class,
            'service-catalog'         => ServiceCatalogController::class,
            'job-cards'               => JobCardController::class,
            'job-card-services'       => JobCardServiceController::class,
            'job-card-parts'          => JobCardPartController::class,
            'customer-vehicles'       => CustomerVehicleController::class,
            'vendors'                 => VendorController::class,
            'sales'                   => SaleController::class,
            'sale-items'              => SaleItemController::class,
            'sale-payments'           => SalePaymentController::class,
            'sale-holds'              => SaleHoldController::class,
            'purchases'               => PurchaseController::class,
            'purchase-items'          => PurchaseItemController::class,
            'purchase-returns'        => PurchaseReturnController::class,
            'purchase-return-items'   => PurchaseReturnItemController::class,
            'vendor-payments'         => VendorPaymentController::class,
        ]);

        // Limited Resources
        Route::resource('brands', BrandController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::patch('brands/{brand}/toggle-status', [BrandController::class, 'toggleStatus'])->name('brands.toggle-status');

        Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::patch('categories/{category}/toggle-status', [CategoryController::class, 'toggleStatus'])->name('categories.toggle-status');

        Route::resource('taxes', TaxController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('units', UnitController::class)->only(['index', 'store', 'update', 'destroy']);
    });
});

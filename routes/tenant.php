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
use App\Http\Controllers\Tenant\ProductController;
use App\Http\Controllers\Tenant\ProductPriceController;
use App\Http\Controllers\Tenant\PosController;
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
use App\Http\Controllers\Tenant\TaxController;
use App\Http\Controllers\Tenant\UnitController;
use App\Http\Controllers\Tenant\UserController;
use App\Http\Controllers\Tenant\VendorPaymentController;
use App\Http\Controllers\Tenant\VendorController;
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
    Route::middleware(['auth:user', 'tenant.branch'])->group(function (): void {

        Route::get('dashboard', fn(): Factory|View => view('tenants.dashboard'))->name('dashboard');
        Route::get('two-step', (new TenantAuthController())->showTwoStep(...))->name('two-step');
        Route::post('two-step', (new TenantAuthController())->verifyTwoStep(...))->name('two-step.verify');
        Route::post('logout', (new TenantAuthController())->logout(...))->name('logout');
        Route::post('branch/switch', [BranchSwitchController::class, 'store'])->name('branch.switch');

        Route::get('pos', [PosController::class, 'index'])->name('pos.index');
        Route::get('pos/scan', [PosController::class, 'scan'])->name('pos.scan');
        Route::get('pos/catalog', [PosController::class, 'catalog'])->name('pos.catalog');
        Route::post('pos', [PosController::class, 'store'])->name('pos.store');
        Route::get('sales/{sale}/print', [SaleController::class, 'print'])->name('sales.print');
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');
        Route::get('reports/export/summary-pdf', [ReportController::class, 'exportSummaryPdf'])->name('reports.export.summary-pdf');

        Route::resources([
            'branches' => BranchController::class,
            'users' => UserController::class,
            'roles' => RoleController::class,
            'customers' => CustomerController::class,
            'products' => ProductController::class,
        ]);

        Route::resource('brands', BrandController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::patch('brands/{brand}/toggle-status', [BrandController::class, 'toggleStatus'])
            ->name('brands.toggle-status');
        Route::resource('categories', CategoryController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::patch('categories/{category}/toggle-status', [CategoryController::class, 'toggleStatus'])
            ->name('categories.toggle-status');
        Route::resource('product-prices', ProductPriceController::class)
            ->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::resource('service-catalog', ServiceCatalogController::class)
            ->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::resource('job-cards', JobCardController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
        Route::resource('job-card-services', JobCardServiceController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
        Route::resource('job-card-parts', JobCardPartController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
        Route::resource('customer-vehicles', CustomerVehicleController::class)
            ->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::resource('vendors', VendorController::class)
            ->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::resource('taxes', TaxController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('units', UnitController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('sales', SaleController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
        Route::resource('sale-items', SaleItemController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
        Route::resource('sale-payments', SalePaymentController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
        Route::resource('sale-holds', SaleHoldController::class)
            ->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::resource('purchases', PurchaseController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
        Route::resource('purchase-items', PurchaseItemController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
        Route::resource('purchase-returns', PurchaseReturnController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
        Route::resource('purchase-return-items', PurchaseReturnItemController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
        Route::resource('vendor-payments', VendorPaymentController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    });
});

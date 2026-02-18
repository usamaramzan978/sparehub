<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\System\AuthController as SystemAuthController;
use App\Http\Controllers\Auth\Tenant\AuthController as TenantAuthController;
use App\Http\Controllers\System\DashboardController as SystemDashboardController;
use App\Http\Controllers\System\PlanController;
use App\Http\Controllers\System\TenantController;
use App\Http\Controllers\System\TenantUserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/', [TenantAuthController::class, 'showLogin'])->name('auth.login');
    Route::post('/', [TenantAuthController::class, 'login'])->middleware(['throttle:60,1'])
        ->name('auth.login.submit');

    Route::get('forgot-password', (new TenantAuthController())->showForgotPassword(...))->name('auth.forgot-password');
    Route::post('forgot-password', (new TenantAuthController())->forgotPassword(...))->name('auth.forgot-password.submit');

    Route::get('reset-password', (new TenantAuthController())->showResetPassword(...))->name('auth.reset-password');
    Route::post('reset-password', (new TenantAuthController())->resetPassword(...))->name('auth.reset-password.submit');
});

Route::prefix('system')->name('system.')->group(function (): void {
    Route::get('/', function () {
        return redirect()->route('system.dashboard');
    })->middleware('auth:system');

    Route::middleware('guest:system')->group(function (): void {
        Route::view('login', 'auth.system.login')->name('login');
        Route::view('forgot-password', 'auth.system.forgot-password')->name('forgot-password');
        Route::view('reset-password', 'auth.system.reset-password')->name('reset-password');

        Route::post('login', (new SystemAuthController())->login(...))->middleware(['throttle:60,1'])->name('login.submit');
        Route::post('forgot-password', (new SystemAuthController())->forgotPassword(...))->name('forgot-password.submit');
        Route::post('reset-password', (new SystemAuthController())->resetPassword(...))->name('reset-password.submit');
    });

    Route::middleware('auth:system')->group(function (): void {
        Route::get('dashboard', SystemDashboardController::class)->name('dashboard');
        Route::resource('plans', PlanController::class)->except(['show']);
        Route::resource('tenants', TenantController::class)->except(['show', 'destroy']);
        Route::resource('tenant-users', TenantUserController::class)->only(['index', 'create', 'store']);
        Route::post('logout', (new SystemAuthController())->logout(...))->name('logout');
    });
});

<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\System\AuthController as SystemAuthController;
use App\Http\Controllers\Auth\Tenant\AuthController as TenantAuthController;
use App\Http\Controllers\System\DashboardController as SystemDashboardController;
use App\Http\Controllers\System\PlanController;
use App\Http\Controllers\System\SupportTicketController as SystemSupportTicketController;
use App\Http\Controllers\System\TenantController;
use App\Http\Controllers\System\TenantUserController;
use Illuminate\Support\Facades\Route;

Route::view('/landing', 'website.landing')->name('website.landing');

Route::middleware('guest')->group(function (): void {
    Route::get('/', (new TenantAuthController())->showLogin(...))->name('auth.login');
    Route::post('/', (new TenantAuthController())->login(...))->middleware(['throttle:6,1'])
        ->name('auth.login.submit');

    // Tenant picker routes
    Route::get('choose-tenant', (new TenantAuthController())->showChooseTenant(...))
        ->name('auth.choose-tenant');

    Route::post('choose-tenant', (new TenantAuthController())->chooseTenant(...))
        ->name('auth.choose-tenant.submit');

    Route::get('forgot-password', (new TenantAuthController())->showForgotPassword(...))->name('auth.forgot-password');
    Route::post('forgot-password', (new TenantAuthController())->forgotPassword(...))->name('auth.forgot-password.submit');

    Route::get('reset-password', (new TenantAuthController())->showResetPassword(...))->name('auth.reset-password');
    Route::post('reset-password', (new TenantAuthController())->resetPassword(...))->name('auth.reset-password.submit');
});

Route::prefix('system')->name('system.')->group(function (): void {

    Route::middleware('guest:system')->group(function (): void {
        Route::view('login', 'auth.system.login')->name('login');
        Route::view('forgot-password', 'auth.system.forgot-password')->name('forgot-password');
        Route::view('reset-password', 'auth.system.reset-password')->name('reset-password');

        Route::post('login', (new SystemAuthController())->login(...))->middleware(['throttle:60,1'])->name('login.submit');
        Route::post('forgot-password', (new SystemAuthController())->forgotPassword(...))->name('forgot-password.submit');
        Route::post('reset-password', (new SystemAuthController())->resetPassword(...))->name('reset-password.submit');
    });

    Route::middleware('auth:system')->group(function (): void {
        Route::get('/', fn () => to_route('system.dashboard'));
        Route::get('dashboard', SystemDashboardController::class)->name('dashboard');
        Route::resource('plans', PlanController::class)->except(['show']);
        Route::resource('tenants', TenantController::class)->except(['show', 'destroy']);
        Route::resource('tenant-users', TenantUserController::class)->only(['index', 'create', 'store']);
        Route::get('support-tickets', [SystemSupportTicketController::class, 'index'])->name('support-tickets.index');
        Route::get('support-tickets/{tenant}/{ticket}/edit', [SystemSupportTicketController::class, 'edit'])->name('support-tickets.edit');
        Route::put('support-tickets/{tenant}/{ticket}', [SystemSupportTicketController::class, 'update'])->name('support-tickets.update');
        Route::post('support-tickets/{tenant}/{ticket}/messages', [SystemSupportTicketController::class, 'storeMessage'])->name('support-tickets.messages.store');
        Route::post('logout', (new SystemAuthController())->logout(...))->name('logout');
    });
});

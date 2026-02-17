<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\System\AuthController as SystemAuthController;
use App\Http\Controllers\Auth\Tenant\AuthController as TenantAuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/', (new TenantAuthController())->showLogin(...))->name('auth.login');
    Route::post('login', (new TenantAuthController())->login(...))
        ->middleware(['throttle:6,1'])
        ->name('auth.login.submit');

    Route::get('register', (new TenantAuthController())->showRegister(...))->name('auth.register');
    Route::post('register', (new TenantAuthController())->register(...))->name('auth.register.submit');

    Route::get('forgot-password', (new TenantAuthController())->showForgotPassword(...))->name('auth.forgot-password');
    Route::post('forgot-password', (new TenantAuthController())->forgotPassword(...))->name('auth.forgot-password.submit');

    Route::get('reset-password', (new TenantAuthController())->showResetPassword(...))->name('auth.reset-password');
    Route::post('reset-password', (new TenantAuthController())->resetPassword(...))->name('auth.reset-password.submit');
});

Route::prefix('system')->name('system.')->group(function (): void {
    Route::middleware('guest:system')->group(function (): void {
        Route::view('login', 'auth.system.login')->name('login');
        Route::view('register', 'auth.system.register')->name('register');
        Route::view('forgot-password', 'auth.system.forgot-password')->name('forgot-password');
        Route::view('reset-password', 'auth.system.reset-password')->name('reset-password');

        Route::post('login', (new SystemAuthController())->login(...))->name('login.submit');
        Route::post('register', (new SystemAuthController())->register(...))->name('register.submit');
        Route::post('forgot-password', (new SystemAuthController())->forgotPassword(...))->name('forgot-password.submit');
        Route::post('reset-password', (new SystemAuthController())->resetPassword(...))->name('reset-password.submit');
    });

    Route::middleware('auth:system')->group(function (): void {
        Route::post('logout', (new SystemAuthController())->logout(...))->name('logout');
    });
});

<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureBranchSelected;
use App\Http\Middleware\EnsureTwoStepVerified;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant.branch' => EnsureBranchSelected::class,
            'tenant.two-step' => EnsureTwoStepVerified::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request): ?string {
            if ($request->routeIs('tenant.login')) {
                return null;
            }

            if ($request->routeIs('system.*')) {
                return route('system.login');
            }

            if (function_exists('tenant') && tenant()) {
                $tenantId = (string) tenant()->getTenantKey();

                return route('tenant.login', ['tenant' => $tenantId]);
            }

            return route('auth.login');
        });

        $middleware->redirectUsersTo(function (Request $request): ?string {
            if ($request->routeIs('tenant.dashboard') || $request->routeIs('system.dashboard')) {
                return null;
            }

            if (Auth::guard('system')->check()) {
                return route('system.dashboard');
            }

            if (function_exists('tenant') && tenant()) {
                $tenantId = (string) tenant()->getTenantKey();

                return route('tenant.dashboard', ['tenant' => $tenantId]);
            }

            return route('auth.login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

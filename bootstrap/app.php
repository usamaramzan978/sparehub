<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureBranchSelected;
use App\Http\Middleware\EnsureTwoStepVerified;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request; // ✅ NOT Illuminate\Support\Facades\Request

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

        $middleware->redirectGuestsTo(function (Request $request) {
            // Don't redirect if already going to login
            if ($request->routeIs('tenant.login')) {
                return null;
            }

            if (function_exists('tenant') && tenant()) {
                $tenantId = (string) tenant()->getTenantKey();

                return route('tenant.login', ['tenant' => $tenantId]);
            }

            return route('auth.login');
        });

        $middleware->redirectUsersTo(function (Request $request) {
            // Don't redirect if already going to dashboard
            if ($request->routeIs('tenant.dashboard')) {
                return null;
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

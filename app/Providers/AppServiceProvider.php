<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $app = $this->app;
        DB::prohibitDestructiveCommands($app->isProduction());
        Model::shouldBeStrict(! $app->isProduction());
        Paginator::useBootstrapFive();

        // Runs on every request after middleware (including tenancy) has fired
        $this->app['events']->listen(
            \Stancl\Tenancy\Events\TenancyInitialized::class,
            function (\Stancl\Tenancy\Events\TenancyInitialized $event) {
                $tenantId = (string) $event->tenancy->tenant->getTenantKey();
                URL::defaults(['tenant' => $tenantId]);
            }
        );

        // Handles guest middleware redirect (authenticated user hits guest route)
        RedirectIfAuthenticated::redirectUsing(function (Request $request): string {
            if (function_exists('tenant') && tenant()) {
                $tenantId = (string) tenant()->getTenantKey();

                return route('tenant.dashboard', ['tenant' => $tenantId]);
            }

            return route('auth.login');
        });
    }
}

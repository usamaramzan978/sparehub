<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Http\Request;
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
        $tenantId = $this->resolveTenantId(request());

        if ($tenantId !== null) {
            URL::defaults(['tenant' => $tenantId]);
        }

        Authenticate::redirectUsing(function (Request $request): string {
            $tenantId = $this->resolveTenantId($request);

            if ($tenantId === null) {
                return route('auth.login');
            }

            return route('tenant.login', ['tenant' => $tenantId]);
        });

        RedirectIfAuthenticated::redirectUsing(function (Request $request): string {
            $tenantId = $this->resolveTenantId($request);

            if ($tenantId === null) {
                return route('auth.login');
            }

            return route('tenant.dashboard', ['tenant' => $tenantId]);
        });
    }

    private function resolveTenantId(Request $request): ?string
    {
        if ($request->route('tenant') !== null) {
            return (string) $request->route('tenant');
        }

        if ($request->segment(1) !== 'firm') {
            return null;
        }

        return $request->segment(2);
    }
}

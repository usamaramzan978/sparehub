<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Branch;
use App\Models\TenantSetting;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Stancl\Tenancy\Events\TenancyInitialized;

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
        $this->app->make(Dispatcher::class)->listen(
            TenancyInitialized::class,
            function (TenancyInitialized $event): void {
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

        View::composer('layouts.shared.header', function ($view): void {
            $currentBranchId = (string) session('tenant.current_branch_id', '');
            $tenantId = function_exists('tenant') && tenant() ? (string) tenant()->getTenantKey() : 'central';
            $user = Auth::guard('user')->user();
            $userId = (string) ($user?->getAuthIdentifier() ?? 'guest');
            $cacheKey = sprintf('header_context:%s:%s:%s', $tenantId, $userId, $currentBranchId);

            $headerContext = Cache::remember($cacheKey, now()->addMinutes(2), function () use ($currentBranchId, $user): array {
                $branches = $user
                    ? Branch::query()->active()->orderBy('name')->get(['id', 'name'])
                    : collect();

                $currentBranchName = $currentBranchId !== ''
                    ? (string) ($branches->firstWhere('id', $currentBranchId)?->name ?? '')
                    : '';

                $tenantTimezone = TenantSetting::query()
                    ->withoutGlobalScopes()
                    ->where('branch_id', $currentBranchId)
                    ->value('timezone') ?? config('app.timezone', 'UTC');

                $headerRole = __('User');
                if ($user && method_exists($user, 'getRoleNames')) {
                    $roleName = $user->getRoleNames()->first();
                    if (is_string($roleName) && $roleName !== '') {
                        $headerRole = Str::headline(str_replace(['-', '_'], ' ', $roleName));
                    }
                }

                return [
                    'headerBranches' => $branches,
                    'headerCurrentBranchId' => $currentBranchId,
                    'headerCurrentBranchName' => $currentBranchName,
                    'headerTenantTimezone' => (string) $tenantTimezone,
                    'headerDisplayName' => (string) ($user?->name ?? __('User')),
                    'headerRole' => $headerRole,
                ];
            });

            $view->with($headerContext);
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureTwoStepVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $required = (bool) $request->session()->get('two_step.required', false);
        $verified = (bool) $request->session()->get('two_step.verified', false);
        $enrollmentRequired = (bool) $request->session()->get('two_step.enrollment_required', false);

        if ($required && ! $verified && $enrollmentRequired) {
            $loadedUser = method_exists($user, 'fresh') ? $user->fresh() : $user;
            $enrollmentAlreadyCompleted = $loadedUser !== null
                && $loadedUser->two_factor_secret !== null
                && $loadedUser->two_factor_verified_at !== null;

            if ($enrollmentAlreadyCompleted) {
                $request->session()->put('two_step.required', false);
                $request->session()->put('two_step.verified', true);
                $request->session()->forget([
                    'two_step.method',
                    'two_step.setup_required',
                    'two_step.enrollment_required',
                    'two_step.code',
                    'two_step.expires_at',
                ]);

                return $next($request);
            }
        }

        if (! $required || $verified) {
            return $next($request);
        }

        if ($request->routeIs('tenant.logout')) {
            return $next($request);
        }

        if ($enrollmentRequired && $request->routeIs('tenant.settings.*')) {
            return $next($request);
        }

        if ($enrollmentRequired && $request->routeIs('tenant.profile.security.*')) {
            return $next($request);
        }

        if ($request->routeIs('tenant.two-step') || $request->routeIs('tenant.two-step.verify')) {
            return $next($request);
        }

        $tenantId = (string) ($request->route('tenant') ?? tenant()?->id ?? '');

        if ($tenantId === '') {
            return to_route('auth.login');
        }

        return $enrollmentRequired
            ? to_route('tenant.profile.security.show', ['tenant' => $tenantId])->with(
                'warning',
                'Two-factor authenticator is enabled but not set up for your account. Complete setup in Security, or disable it from Settings if enabled by mistake.'
            )
            : to_route('tenant.two-step', ['tenant' => $tenantId])->with(
                'warning',
                'Two-step verification is required before continuing.'
            );
    }
}

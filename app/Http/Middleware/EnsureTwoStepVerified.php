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
        if (! $request->user()) {
            return $next($request);
        }

        $required = (bool) $request->session()->get('two_step.required', false);
        $verified = (bool) $request->session()->get('two_step.verified', false);
        $enrollmentRequired = (bool) $request->session()->get('two_step.enrollment_required', false);

        if (! $required || $verified) {
            return $next($request);
        }

        if ($request->routeIs('tenant.logout')) {
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
            ? to_route('tenant.profile.security.show', ['tenant' => $tenantId])
            : to_route('tenant.two-step', ['tenant' => $tenantId]);
    }
}

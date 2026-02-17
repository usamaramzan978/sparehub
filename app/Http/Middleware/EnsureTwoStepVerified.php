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

        if (! $required || $verified) {
            return $next($request);
        }

        if ($request->routeIs('tenant.two-step') || $request->routeIs('tenant.two-step.verify') || $request->routeIs('tenant.logout')) {
            return $next($request);
        }

        $tenantId = (string) ($request->route('tenant') ?? tenant()?->id ?? '');

        if ($tenantId === '') {
            return to_route('auth.login');
        }

        return to_route('tenant.two-step', [
            'tenant' => $tenantId,
        ]);
    }
}

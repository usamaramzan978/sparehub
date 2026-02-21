<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Models\TenantSetting;
use Closure;
use DateTimeZone;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureBranchSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $session = $request->session();
            $currentBranchId = $session->get('tenant.current_branch_id');

            if (! $currentBranchId || ! $this->branchIsActive($currentBranchId)) {
                $fallbackBranchId = $this->resolveFallbackBranchId($request->user()?->branch_id);

                if ($fallbackBranchId) {
                    $session->put('tenant.current_branch_id', $fallbackBranchId);
                } else {
                    $session->forget('tenant.current_branch_id');
                }
            }

            $this->applyTenantTimezone((string) $session->get('tenant.current_branch_id'));
        }

        return $next($request);
    }

    private function branchIsActive(string $branchId): bool
    {
        return Branch::query()
            ->active()
            ->whereKey($branchId)
            ->exists();
    }

    private function resolveFallbackBranchId(?string $preferredBranchId): ?string
    {
        if ($preferredBranchId && $this->branchIsActive($preferredBranchId)) {
            return $preferredBranchId;
        }

        return Branch::query()
            ->active()
            ->orderBy('name')
            ->value('id');
    }

    private function applyTenantTimezone(string $branchId): void
    {
        if ($branchId === '') {
            return;
        }

        $timezone = TenantSetting::query()
            ->withoutGlobalScope('session_branch')
            ->where('branch_id', $branchId)
            ->value('timezone');

        if (! is_string($timezone) || ! in_array($timezone, DateTimeZone::listIdentifiers(), true)) {
            return;
        }

        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);
    }
}

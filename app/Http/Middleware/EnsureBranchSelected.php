<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Branch;
use Closure;
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
}

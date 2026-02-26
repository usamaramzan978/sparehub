<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Branch;

use App\Models\Branch;
use App\Support\AuditTimelineLogger;
use App\Support\HeaderContextCache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

final class CreateBranchAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Branch
    {
        $maxBranches = (int) config('tenancy.limits.max_branches', 3);

        if ($maxBranches > 0 && Branch::query()->count() >= $maxBranches) {
            throw ValidationException::withMessages([
                'code' => [sprintf('Maximum %d branches are allowed for this tenant.', $maxBranches)],
            ]);
        }

        $branch = Branch::query()->create($data);
        HeaderContextCache::bumpForCurrentTenant();

        AuditTimelineLogger::log(
            event: 'branch_created',
            description: 'Branch created.',
            causer: Auth::guard('user')->user(),
            subject: $branch,
            properties: [
                'branch_id' => (string) $branch->id,
                'branch_name' => $branch->name,
            ],
        );

        return $branch;
    }
}

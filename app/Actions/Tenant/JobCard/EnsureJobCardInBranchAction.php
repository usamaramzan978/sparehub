<?php

declare(strict_types=1);

namespace App\Actions\Tenant\JobCard;

use App\Models\JobCard;

final class EnsureJobCardInBranchAction
{
    public function handle(JobCard $jobCard, string $branchId): JobCard
    {
        abort_if($jobCard->branch_id !== $branchId, 404);

        return $jobCard;
    }
}

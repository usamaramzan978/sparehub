<?php

declare(strict_types=1);

namespace App\Actions\Tenant\JobCardService;

use App\Models\JobCard;
use App\Models\JobCardService;

final class EnsureJobCardServiceInBranchAction
{
    public function handle(JobCardService $jobCardService, string $branchId): JobCardService
    {
        $jobCard = $jobCardService->jobCard;

        abort_if(! $jobCard instanceof JobCard || $jobCard->branch_id !== $branchId, 404);

        return $jobCardService;
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Tenant\JobCardPart;

use App\Models\JobCard;
use App\Models\JobCardPart;

final class EnsureJobCardPartInBranchAction
{
    public function handle(JobCardPart $jobCardPart, string $branchId): JobCardPart
    {
        $jobCard = $jobCardPart->jobCard;

        abort_if(! $jobCard instanceof JobCard || $jobCard->branch_id !== $branchId, 404);

        return $jobCardPart;
    }
}

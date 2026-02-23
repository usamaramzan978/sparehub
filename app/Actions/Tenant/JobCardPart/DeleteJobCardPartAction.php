<?php

declare(strict_types=1);

namespace App\Actions\Tenant\JobCardPart;

use App\Models\JobCardPart;

final class DeleteJobCardPartAction
{
    public function handle(JobCardPart $jobCardPart): bool
    {
        return (bool) $jobCardPart->delete();
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Tenant\JobCardService;

use App\Models\JobCardService;

final class DeleteJobCardServiceAction
{
    public function handle(JobCardService $jobCardService): bool
    {
        return (bool) $jobCardService->delete();
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Tenant\JobCardService;

use App\Models\JobCardService;

final class UpdateJobCardServiceAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(JobCardService $jobCardService, array $payload): bool
    {
        $payload['line_total'] = (float) $payload['qty'] * (float) $payload['rate'];

        return $jobCardService->update($payload);
    }
}

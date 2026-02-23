<?php

declare(strict_types=1);

namespace App\Actions\Tenant\JobCardService;

use App\Models\JobCardService;

final class CreateJobCardServiceAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload): JobCardService
    {
        $payload['line_total'] = (float) $payload['qty'] * (float) $payload['rate'];

        return JobCardService::query()->create($payload);
    }
}

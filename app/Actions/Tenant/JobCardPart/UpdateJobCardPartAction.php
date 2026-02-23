<?php

declare(strict_types=1);

namespace App\Actions\Tenant\JobCardPart;

use App\Models\JobCardPart;

final class UpdateJobCardPartAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(JobCardPart $jobCardPart, array $payload): bool
    {
        $payload['line_total'] = (float) $payload['qty'] * (float) $payload['unit_price'];

        return $jobCardPart->update($payload);
    }
}

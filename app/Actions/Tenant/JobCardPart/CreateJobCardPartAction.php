<?php

declare(strict_types=1);

namespace App\Actions\Tenant\JobCardPart;

use App\Models\JobCardPart;

final class CreateJobCardPartAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload): JobCardPart
    {
        $payload['line_total'] = (float) $payload['qty'] * (float) $payload['unit_price'];

        return JobCardPart::query()->create($payload);
    }
}

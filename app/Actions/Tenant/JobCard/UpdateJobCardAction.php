<?php

declare(strict_types=1);

namespace App\Actions\Tenant\JobCard;

use App\Models\JobCard;
use App\Support\AuditTimelineLogger;
use Illuminate\Support\Facades\Auth;

final class UpdateJobCardAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(JobCard $jobCard, array $payload, string $branchId): bool
    {
        $payload['branch_id'] = $branchId;

        $updated = $jobCard->update($payload);

        AuditTimelineLogger::log(
            event: 'job_card_updated',
            description: 'Job card updated.',
            causer: Auth::guard('user')->user(),
            subject: $jobCard,
            properties: [
                'job_card_id' => (string) $jobCard->id,
                'job_no' => (string) $jobCard->job_no,
                'changed_attributes' => array_keys($payload),
            ],
        );

        return $updated;
    }
}

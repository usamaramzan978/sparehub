<?php

declare(strict_types=1);

namespace App\Actions\Tenant\JobCard;

use App\Models\JobCard;
use App\Support\AuditTimelineLogger;
use BackedEnum;
use Illuminate\Support\Facades\Auth;

final class CreateJobCardAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, string $branchId, mixed $createdBy): JobCard
    {
        $payload['branch_id'] = $branchId;
        $payload['created_by'] = $createdBy;

        $jobCard = JobCard::query()->create($payload);

        AuditTimelineLogger::log(
            event: 'job_card_created',
            description: 'Job card created.',
            causer: Auth::guard('user')->user(),
            subject: $jobCard,
            properties: [
                'job_card_id' => (string) $jobCard->id,
                'job_no' => (string) $jobCard->job_no,
                'status' => $this->statusValue($jobCard->status),
            ],
        );

        return $jobCard;
    }

    private function statusValue(mixed $status): string
    {
        if ($status instanceof BackedEnum) {
            return (string) $status->value;
        }

        return (string) $status;
    }
}

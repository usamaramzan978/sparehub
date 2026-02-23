<?php

declare(strict_types=1);

namespace App\Actions\Tenant\JobCard;

use App\Models\JobCard;
use App\Support\AuditTimelineLogger;
use BackedEnum;
use Illuminate\Support\Facades\Auth;

final class DeleteJobCardAction
{
    public function handle(JobCard $jobCard): bool
    {
        $snapshot = [
            'job_card_id' => (string) $jobCard->id,
            'job_no' => (string) $jobCard->job_no,
            'status' => $this->statusValue($jobCard->status),
        ];

        $deleted = (bool) $jobCard->delete();

        AuditTimelineLogger::log(
            event: 'job_card_deleted',
            description: 'Job card deleted.',
            causer: Auth::guard('user')->user(),
            subject: $jobCard,
            properties: $snapshot,
        );

        return $deleted;
    }

    private function statusValue(mixed $status): string
    {
        if ($status instanceof BackedEnum) {
            return (string) $status->value;
        }

        return (string) $status;
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Tenant\JobCard;

use App\Models\JobCard;
use App\Models\JobCardPart;
use App\Models\JobCardService;
use Illuminate\Support\Facades\DB;

final class SyncJobCardLinesAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(JobCard $jobCard, array $payload): void
    {
        /** @var array<int, array<string, mixed>> $serviceRows */
        $serviceRows = is_array($payload['services'] ?? null) ? $payload['services'] : [];
        /** @var array<int, array<string, mixed>> $partRows */
        $partRows = is_array($payload['parts'] ?? null) ? $payload['parts'] : [];

        DB::connection('tenant')->transaction(function () use ($jobCard, $serviceRows, $partRows): void {
            $this->syncServices($jobCard, $serviceRows);
            $this->syncParts($jobCard, $partRows);
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncServices(JobCard $jobCard, array $rows): void
    {
        $keptIds = [];

        foreach ($rows as $row) {
            $serviceId = isset($row['id']) && is_string($row['id']) ? $row['id'] : null;
            $attributes = [
                'service_catalog_id' => $this->nullableString($row['service_catalog_id'] ?? null),
                'technician_id' => $this->nullableString($row['technician_id'] ?? null),
                'service_name' => (string) ($row['service_name'] ?? ''),
                'qty' => (float) ($row['qty'] ?? 0),
                'rate' => (float) ($row['rate'] ?? 0),
                'line_total' => (float) ($row['qty'] ?? 0) * (float) ($row['rate'] ?? 0),
                'status' => (string) ($row['status'] ?? ''),
                'remarks' => $this->nullableString($row['remarks'] ?? null),
            ];

            if ($serviceId !== null) {
                $existing = $jobCard->services()->whereKey($serviceId)->first();

                if ($existing instanceof JobCardService) {
                    $existing->update($attributes);
                    $keptIds[] = $existing->id;

                    continue;
                }
            }

            $created = $jobCard->services()->create($attributes);
            $keptIds[] = $created->id;
        }

        $deleteQuery = $jobCard->services();
        if ($keptIds !== []) {
            $deleteQuery->whereNotIn('id', $keptIds);
        }
        $deleteQuery->delete();
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncParts(JobCard $jobCard, array $rows): void
    {
        $keptIds = [];

        foreach ($rows as $row) {
            $partId = isset($row['id']) && is_string($row['id']) ? $row['id'] : null;
            $attributes = [
                'product_id' => (string) ($row['product_id'] ?? ''),
                'qty' => (float) ($row['qty'] ?? 0),
                'unit_price' => (float) ($row['unit_price'] ?? 0),
                'line_total' => (float) ($row['qty'] ?? 0) * (float) ($row['unit_price'] ?? 0),
            ];

            if ($partId !== null) {
                $existing = $jobCard->parts()->whereKey($partId)->first();

                if ($existing instanceof JobCardPart) {
                    $existing->update($attributes);
                    $keptIds[] = $existing->id;

                    continue;
                }
            }

            $created = $jobCard->parts()->create($attributes);
            $keptIds[] = $created->id;
        }

        $deleteQuery = $jobCard->parts();
        if ($keptIds !== []) {
            $deleteQuery->whereNotIn('id', $keptIds);
        }
        $deleteQuery->delete();
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = mb_trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}

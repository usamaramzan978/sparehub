<?php

declare(strict_types=1);

namespace App\Actions\Tenant\EmployeeAttendance;

use App\Models\EmployeeAttendance;
use Illuminate\Support\Facades\Date;

final class UpsertEmployeeAttendanceAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, string $branchId): EmployeeAttendance
    {
        $action = (string) $payload['action'];

        $attendance = EmployeeAttendance::query()->firstOrNew([
            'branch_id' => $branchId,
            'user_id' => $payload['user_id'],
            'attendance_date' => $payload['attendance_date'],
        ]);

        if ($action === 'check_in') {
            $attendance->check_in_at = now()->toDateTimeString();
        }

        if ($action === 'check_out') {
            if ($attendance->check_in_at === null) {
                $attendance->check_in_at = now()->toDateTimeString();
            }

            $attendance->check_out_at = now()->toDateTimeString();
        }

        if ($action === 'mark_absent') {
            $attendance->check_in_at = null;
            $attendance->check_out_at = null;
            $attendance->total_minutes = null;
        }

        if ($attendance->check_in_at !== null && $attendance->check_out_at !== null) {
            $checkInAt = Date::parse($attendance->check_in_at);
            $checkOutAt = Date::parse($attendance->check_out_at);

            if ($checkOutAt->greaterThan($checkInAt)) {
                $attendance->total_minutes = (int) $checkInAt->diffInMinutes($checkOutAt);
            }
        }

        $attendance->notes = $payload['notes'] ?? null;
        $attendance->save();

        return $attendance;
    }
}

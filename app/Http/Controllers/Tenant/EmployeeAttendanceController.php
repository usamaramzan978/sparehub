<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\EmployeeAttendanceRequest;
use App\Models\EmployeeAttendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class EmployeeAttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $attendanceDateInput = mb_trim($request->string('attendance_date')->toString());
        $attendanceDate = $attendanceDateInput !== ''
            ? Carbon::parse($attendanceDateInput)->toDateString()
            : now()->toDateString();

        $employees = User::query()
            ->where('branch_id', $branchId)
            ->orderBy('name')
            ->get();

        $records = EmployeeAttendance::query()
            ->where('branch_id', $branchId)
            ->whereDate('attendance_date', $attendanceDate)
            ->get()
            ->keyBy('user_id');

        $checkedInCount = $records->filter(fn (EmployeeAttendance $record): bool => $record->check_in_at !== null)->count();
        $checkedOutCount = $records->filter(fn (EmployeeAttendance $record): bool => $record->check_out_at !== null)->count();

        return view('tenants.employee-attendances.index', [
            'employees' => $employees,
            'records' => $records,
            'attendanceDate' => $attendanceDate,
            'summary' => [
                'employees_count' => $employees->count(),
                'checked_in_count' => $checkedInCount,
                'checked_out_count' => $checkedOutCount,
                'missing_checkout_count' => max($checkedInCount - $checkedOutCount, 0),
            ],
        ]);
    }

    public function store(EmployeeAttendanceRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $branchId = $this->currentBranchId();
        $action = (string) $payload['action'];

        $attendance = EmployeeAttendance::query()->firstOrNew([
            'branch_id' => $branchId,
            'user_id' => $payload['user_id'],
            'attendance_date' => $payload['attendance_date'],
        ]);

        if ($action === 'check_in') {
            $attendance->check_in_at = now();
        }

        if ($action === 'check_out') {
            if ($attendance->check_in_at === null) {
                $attendance->check_in_at = now();
            }

            $attendance->check_out_at = now();
        }

        if ($action === 'mark_absent') {
            $attendance->check_in_at = null;
            $attendance->check_out_at = null;
            $attendance->total_minutes = null;
        }

        if ($attendance->check_in_at !== null && $attendance->check_out_at !== null) {
            $checkInAt = Carbon::parse($attendance->check_in_at);
            $checkOutAt = Carbon::parse($attendance->check_out_at);

            if ($checkOutAt->greaterThan($checkInAt)) {
                $attendance->total_minutes = $checkInAt->diffInMinutes($checkOutAt);
            }
        }

        $attendance->notes = $payload['notes'] ?? null;
        $attendance->save();

        return to_route('tenant.employee-attendances.index', [
            'tenant' => (string) tenant('id'),
            'attendance_date' => $payload['attendance_date'],
        ])->with('status', 'Attendance updated.');
    }
}

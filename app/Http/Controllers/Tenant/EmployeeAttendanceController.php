<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\EmployeeAttendance\UpsertEmployeeAttendanceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\EmployeeAttendanceRequest;
use App\Models\EmployeeAttendance;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;

final class EmployeeAttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $attendanceDateInput = mb_trim($request->string('attendance_date')->toString());
        $search = mb_trim($request->string('search')->toString());
        $attendanceDate = $attendanceDateInput !== ''
            ? Date::parse($attendanceDateInput)->toDateString()
            : now()->toDateString();

        $employees = User::query()
            ->where('branch_id', $branchId)
            ->when(filled($search), function ($query) use ($search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder
                        ->where('name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('email', 'like', sprintf('%%%s%%', $search));
                });
            })
            ->orderBy('name')
            ->get();

        $records = EmployeeAttendance::query()
            ->where('branch_id', $branchId)
            ->whereDate('attendance_date', $attendanceDate)
            ->whereIn('user_id', $employees->pluck('id'))
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

    public function store(EmployeeAttendanceRequest $request, UpsertEmployeeAttendanceAction $action): RedirectResponse
    {
        $payload = $request->validated();
        $action->handle($payload, $this->currentBranchId());

        return to_route('tenant.employee-attendances.index', [
            'tenant' => (string) tenant('id'),
            'attendance_date' => $payload['attendance_date'],
        ])->with('status', 'Attendance updated.');
    }
}

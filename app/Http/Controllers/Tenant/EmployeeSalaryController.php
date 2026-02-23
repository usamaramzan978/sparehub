<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\EmployeeSalary\UpsertEmployeeSalaryAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\EmployeeSalaryRequest;
use App\Models\EmployeeSalary;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;

final class EmployeeSalaryController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $salaryMonth = $request->string('salary_month')->toString();
        $selectedMonth = $salaryMonth !== ''
            ? Date::createFromFormat('Y-m', $salaryMonth)->startOfMonth()
            : now()->startOfMonth();

        $employees = User::query()
            ->where('branch_id', $branchId)
            ->orderBy('name')
            ->get();

        $records = EmployeeSalary::query()
            ->where('branch_id', $branchId)
            ->whereDate('salary_month', $selectedMonth->toDateString())
            ->get()
            ->keyBy('user_id');

        $paidTotal = (float) $records->filter(fn (EmployeeSalary $record): bool => $record->paid_at !== null)->sum('net_salary');
        $unpaidTotal = (float) $records->filter(fn (EmployeeSalary $record): bool => $record->paid_at === null)->sum('net_salary');

        return view('tenants.employee-salaries.index', [
            'employees' => $employees,
            'records' => $records,
            'salaryMonth' => $selectedMonth->format('Y-m'),
            'summary' => [
                'employees_count' => $employees->count(),
                'configured_count' => $records->count(),
                'paid_total' => $paidTotal,
                'unpaid_total' => $unpaidTotal,
            ],
        ]);
    }

    public function store(EmployeeSalaryRequest $request, UpsertEmployeeSalaryAction $action): RedirectResponse
    {
        $payload = $request->validated();
        $action->handle($payload, $this->currentBranchId());

        $salaryMonth = Date::parse((string) $payload['salary_month'])->startOfMonth()->toDateString();

        return to_route('tenant.employee-salaries.index', [
            'tenant' => (string) tenant('id'),
            'salary_month' => Date::parse($salaryMonth)->format('Y-m'),
        ])->with('status', 'Salary record updated.');
    }
}

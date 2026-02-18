<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class EmployeeSalaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $branchId = session('tenant.current_branch_id');
        $employeeExists = Rule::exists('users', 'id');

        if (is_string($branchId) && $branchId !== '') {
            $employeeExists = $employeeExists->where(fn ($query) => $query->where('branch_id', $branchId));
        }

        return [
            'user_id' => ['required', 'uuid', $employeeExists],
            'salary_month' => ['required', 'date'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'bonus' => ['nullable', 'numeric', 'min:0'],
            'deduction' => ['nullable', 'numeric', 'min:0'],
            'action' => ['required', Rule::in(['save', 'mark_paid'])],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $salaryMonth = mb_trim((string) $this->input('salary_month'));

        if (preg_match('/^\d{4}-\d{2}$/', $salaryMonth) === 1) {
            $this->merge(['salary_month' => $salaryMonth.'-01']);
        }
    }
}

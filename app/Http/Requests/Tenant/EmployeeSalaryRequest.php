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

    /**
     *  array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'The :attribute field is required.',
            'email' => 'The :attribute must be a valid email address.',
            'uuid' => 'The :attribute must be a valid UUID.',
            'date' => 'The :attribute must be a valid date.',
            'numeric' => 'The :attribute must be a valid number.',
            'min' => 'The :attribute is below the minimum allowed value.',
            'max' => 'The :attribute exceeds the maximum allowed value.',
            'in' => 'The selected :attribute is invalid.',
            'exists' => 'The selected :attribute is invalid.',
            'unique' => 'The :attribute has already been taken.',
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

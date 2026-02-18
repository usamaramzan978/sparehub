<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class EmployeeAttendanceRequest extends FormRequest
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
            'attendance_date' => ['required', 'date'],
            'action' => ['required', Rule::in(['check_in', 'check_out', 'mark_absent'])],
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
}

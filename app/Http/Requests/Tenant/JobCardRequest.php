<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\JobCardStatus;
use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class JobCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $jobCardId = $this->route('job_card')?->id;
        $branchId = session('tenant.current_branch_id');
        $jobNoUnique = Rule::unique('job_cards', 'job_no')->ignore($jobCardId);
        $customerExists = Rule::exists('customers', 'id');
        $vehicleExists = Rule::exists('customer_vehicles', 'id');
        $employeeExists = Rule::exists('users', 'id');

        if (is_string($branchId) && $branchId !== '') {
            $jobNoUnique = $jobNoUnique->where(fn ($query) => $query->where('branch_id', $branchId));
            $customerExists = $customerExists->where(fn ($query) => $query->where('branch_id', $branchId));
            $employeeExists = $employeeExists->where(fn ($query) => $query->where('branch_id', $branchId));
            $vehicleExists = $vehicleExists->where(fn ($query) => $query->whereIn(
                'customer_id',
                Customer::query()->where('branch_id', $branchId)->select('id')
            ));
        }

        return [
            'customer_id' => ['required', 'uuid', $customerExists],
            'vehicle_id' => ['nullable', 'uuid', $vehicleExists],
            'assigned_employee_id' => ['nullable', 'uuid', $employeeExists],
            'job_no' => ['required', 'string', 'max:40', $jobNoUnique],
            'job_date' => ['required', 'date'],
            'status' => ['required', Rule::enum(JobCardStatus::class)],
            'meter_reading' => ['nullable', 'numeric', 'min:0'],
            'next_reading' => ['nullable', 'numeric', 'min:0'],
            'total_visits' => ['nullable', 'integer', 'min:0'],
            'remarks' => ['nullable', 'string'],
            'in_time' => ['nullable', 'date'],
            'out_time' => ['nullable', 'date', 'after_or_equal:in_time'],
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

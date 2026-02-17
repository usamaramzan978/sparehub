<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CustomerVehicleRequest extends FormRequest
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
        $vehicleId = $this->route('customer_vehicle')?->id;
        $branchId = session('tenant.current_branch_id');
        $customerExistsRule = Rule::exists('customers', 'id');

        if (is_string($branchId) && $branchId !== '') {
            $customerExistsRule = $customerExistsRule->where(fn ($query) => $query->where('branch_id', $branchId));
        }

        return [
            'customer_id' => ['required', 'uuid', $customerExistsRule],
            'registration_no' => ['required', 'string', 'max:50', Rule::unique('customer_vehicles', 'registration_no')->ignore($vehicleId)],
            'model' => ['nullable', 'string', 'max:120'],
            'year' => ['nullable', 'integer', 'between:1950,2100'],
            'chassis_no' => ['nullable', 'string', 'max:80'],
            'engine_no' => ['nullable', 'string', 'max:80'],
            'meter_reading' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'Please select a customer.',
            'registration_no.required' => 'Registration number is required.',
            'registration_no.unique' => 'This registration number is already in use.',
            'year.between' => 'Please enter a valid model year.',
            'meter_reading.min' => 'Meter reading cannot be negative.',
        ];
    }
}

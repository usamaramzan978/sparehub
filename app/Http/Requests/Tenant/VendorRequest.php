<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class VendorRequest extends FormRequest
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
        $vendorId = $this->route('vendor')?->id;
        $branchId = session('tenant.current_branch_id');
        $codeUnique = Rule::unique('vendors', 'code')->ignore($vendorId);

        if (is_string($branchId) && $branchId !== '') {
            $codeUnique = $codeUnique->where(fn ($query) => $query->where('branch_id', $branchId));
        }

        return [
            'code' => ['required', 'string', 'max:40', $codeUnique],
            'name' => ['required', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'cnic' => ['nullable', 'string', 'max:25'],
            'ntn' => ['nullable', 'string', 'max:25'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'opening_balance' => ['nullable', 'numeric'],
            'status' => ['required', Rule::enum(RecordStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Vendor code is required.',
            'code.unique' => 'This vendor code already exists for the selected branch.',
            'name.required' => 'Vendor name is required.',
            'email.email' => 'Please enter a valid email address.',
            'status.required' => 'Please select a vendor status.',
        ];
    }
}

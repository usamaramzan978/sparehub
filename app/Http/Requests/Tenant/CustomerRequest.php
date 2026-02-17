<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\CustomerStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CustomerRequest extends FormRequest
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
        $customerId = $this->route('customer')?->id;
        $branchId = session('tenant.current_branch_id');
        $codeUnique = Rule::unique('customers', 'code')->ignore($customerId);

        if (is_string($branchId) && $branchId !== '') {
            $codeUnique = $codeUnique->where(fn ($query) => $query->where('branch_id', $branchId));
        }

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                $codeUnique,
            ],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:255'],
            'cnic' => ['nullable', 'string', 'max:25'],
            'ntn' => ['nullable', 'string', 'max:25'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'opening_balance' => ['nullable', 'numeric'],
            'status' => ['required', Rule::enum(CustomerStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Customer code is required.',
            'code.unique' => 'This customer code already exists for the selected branch.',
            'name.required' => 'Customer name is required.',
            'email.email' => 'Please enter a valid email address.',
            'credit_limit.min' => 'Credit limit must be 0 or greater.',
            'status.required' => 'Please select a customer status.',
        ];
    }
}

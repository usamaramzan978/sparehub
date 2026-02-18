<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaleHoldRequest extends FormRequest
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
        $saleHoldId = $this->route('sale_hold')?->id;
        $branchId = session('tenant.current_branch_id');
        $holdUnique = Rule::unique('sale_holds', 'hold_no')->ignore($saleHoldId);
        $customerExists = Rule::exists('customers', 'id');

        if (is_string($branchId) && $branchId !== '') {
            $holdUnique = $holdUnique->where(fn ($query) => $query->where('branch_id', $branchId));
            $customerExists = $customerExists->where(fn ($query) => $query->where('branch_id', $branchId));
        }

        return [
            'customer_id' => ['nullable', 'uuid', $customerExists],
            'hold_no' => ['required', 'string', 'max:40', $holdUnique],
            'payload' => ['required', 'json'],
            'expires_at' => ['nullable', 'date'],
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

<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class JobCardPartRequest extends FormRequest
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
        $branchId = session('tenant.current_branch_id');
        $jobCardExists = Rule::exists('job_cards', 'id');
        $productExists = Rule::exists('products', 'id');

        if (is_string($branchId) && $branchId !== '') {
            $jobCardExists = $jobCardExists->where(fn ($query) => $query->where('branch_id', $branchId));
        }

        return [
            'job_card_id' => ['required', 'uuid', $jobCardExists],
            'product_id' => ['required', 'uuid', $productExists],
            'qty' => ['required', 'numeric', 'gt:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
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

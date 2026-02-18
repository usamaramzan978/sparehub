<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\SaleLineType;
use App\Models\JobCard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaleItemRequest extends FormRequest
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
        $saleExists = Rule::exists('sales', 'id');
        $serviceCatalogExists = Rule::exists('service_catalog', 'id');
        $jobCardServiceExists = Rule::exists('job_card_services', 'id');

        if (is_string($branchId) && $branchId !== '') {
            $saleExists = $saleExists->where(fn ($query) => $query->where('branch_id', $branchId));
            $serviceCatalogExists = $serviceCatalogExists->where(fn ($query) => $query->where('branch_id', $branchId));
            $jobCardServiceExists = $jobCardServiceExists->where(fn ($query) => $query->whereIn(
                'job_card_id',
                JobCard::query()->where('branch_id', $branchId)->select('id')
            ));
        }

        return [
            'sale_id' => ['required', 'uuid', $saleExists],
            'product_id' => ['nullable', 'uuid', Rule::exists('products', 'id')],
            'service_catalog_id' => ['nullable', 'uuid', $serviceCatalogExists],
            'job_card_service_id' => ['nullable', 'uuid', $jobCardServiceExists],
            'line_type' => ['required', Rule::enum(SaleLineType::class)],
            'description' => ['nullable', 'string', 'max:200'],
            'qty' => ['required', 'numeric', 'gt:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
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

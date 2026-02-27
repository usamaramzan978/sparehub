<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\InvoiceType;
use App\Enums\SaleLineType;
use App\Enums\SaleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class SaleRequest extends FormRequest
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
        $saleId = $this->route('sale')?->id;
        $branchId = session('tenant.current_branch_id');
        $invoiceUnique = Rule::unique('sales', 'invoice_no')->ignore($saleId);
        $customerExists = Rule::exists('customers', 'id');
        $jobCardExists = Rule::exists('job_cards', 'id');
        $serviceCatalogExists = Rule::exists('service_catalog', 'id');
        $mechanicExists = Rule::exists('users', 'id');

        if (is_string($branchId) && $branchId !== '') {
            $invoiceUnique = $invoiceUnique->where(fn ($query) => $query->where('branch_id', $branchId));
            $customerExists = $customerExists->where(fn ($query) => $query->where('branch_id', $branchId));
            $jobCardExists = $jobCardExists->where(fn ($query) => $query->where('branch_id', $branchId));
            $serviceCatalogExists = $serviceCatalogExists->where(fn ($query) => $query->where('branch_id', $branchId));
            $mechanicExists = $mechanicExists->where(fn ($query) => $query->where('branch_id', $branchId));
        }

        return [
            'customer_id' => ['nullable', 'uuid', $customerExists],
            'job_card_id' => ['nullable', 'uuid', $jobCardExists],
            'invoice_no' => ['required', 'string', 'max:40', $invoiceUnique],
            'invoice_date' => ['required', 'date'],
            'status' => ['required', Rule::enum(SaleStatus::class)],
            'invoice_type' => ['required', Rule::enum(InvoiceType::class)],
            'sub_total' => ['nullable', 'numeric', 'min:0'],
            'discount_total' => ['nullable', 'numeric', 'min:0'],
            'tax_total' => ['nullable', 'numeric', 'min:0'],
            'grand_total' => ['nullable', 'numeric', 'min:0'],
            'paid_total' => ['nullable', 'numeric', 'min:0'],
            'balance_due' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
            'posted_at' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.line_type' => ['required', Rule::enum(SaleLineType::class)],
            'items.*.product_id' => [
                'nullable',
                'uuid',
                Rule::exists('products', 'id'),
            ],
            'items.*.service_catalog_id' => [
                'nullable',
                'uuid',
                $serviceCatalogExists,
            ],
            'items.*.job_card_service_id' => [
                'nullable',
                'uuid',
                Rule::exists('job_card_services', 'id'),
            ],
            'items.*.mechanic_id' => [
                'nullable',
                'uuid',
                $mechanicExists,
            ],
            'items.*.description' => ['nullable', 'string', 'max:200'],
            'items.*.qty' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.mechanic_charge' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $items = $this->input('items', []);

            if (! is_array($items)) {
                return;
            }

            foreach ($items as $index => $item) {
                $lineType = is_array($item) ? ($item['line_type'] ?? null) : null;

                if ($lineType === SaleLineType::PRODUCT->value && empty($item['product_id'])) {
                    $validator->errors()->add(sprintf('items.%s.product_id', $index), 'The product field is required for product line type.');
                }

                if ($lineType === SaleLineType::SERVICE->value && empty($item['service_catalog_id'])) {
                    $validator->errors()->add(sprintf('items.%s.service_catalog_id', $index), 'The service field is required for service line type.');
                }
            }
        });
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

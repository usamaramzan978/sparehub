<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\PurchaseStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class PurchaseRequest extends FormRequest
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
        $purchaseId = $this->route('purchase')?->id;
        $branchId = session('tenant.current_branch_id');

        $vendorExists = Rule::exists('vendors', 'id');
        $purchaseUnique = Rule::unique('purchases', 'purchase_no')->ignore($purchaseId);
        $productExists = Rule::exists('products', 'id');

        if (is_string($branchId) && $branchId !== '') {
            $vendorExists = $vendorExists->where(fn ($query) => $query->where('branch_id', $branchId));
            $purchaseUnique = $purchaseUnique->where(fn ($query) => $query->where('branch_id', $branchId));
            $productExists = $productExists->where(fn ($query) => $query->whereNull('deleted_at'));
        }

        return [
            'vendor_id' => ['required', 'uuid', $vendorExists],
            'purchase_no' => ['nullable', 'string', 'max:40', $purchaseUnique],
            'purchase_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'status' => ['required', Rule::enum(PurchaseStatus::class)],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid', $productExists],
            'items.*.qty' => ['required', 'numeric', 'gt:0'],
            'items.*.cost' => ['required', 'numeric', 'min:0'],
            'items.*.mrp' => ['required', 'numeric', 'min:0'],
            'items.*.retail_price' => ['required', 'numeric', 'min:0'],
            'items.*.wholesale_price' => ['required', 'numeric', 'min:0'],
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
                if (! is_array($item) || empty($item['product_id'])) {
                    $validator->errors()->add(sprintf('items.%s.product_id', $index), 'The product field is required.');
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

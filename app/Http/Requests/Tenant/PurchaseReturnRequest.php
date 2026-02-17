<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\PurchaseReturnStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class PurchaseReturnRequest extends FormRequest
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
        $purchaseReturnId = $this->route('purchase_return')?->id;
        $branchId = session('tenant.current_branch_id');

        $vendorExists = Rule::exists('vendors', 'id');
        $purchaseExists = Rule::exists('purchases', 'id');
        $purchaseItemExists = Rule::exists('purchase_items', 'id');
        $returnUnique = Rule::unique('purchase_returns', 'return_no')->ignore($purchaseReturnId);
        $productExists = Rule::exists('products', 'id');

        if (is_string($branchId) && $branchId !== '') {
            $vendorExists = $vendorExists->where(fn ($query) => $query->where('branch_id', $branchId));
            $purchaseExists = $purchaseExists->where(fn ($query) => $query->where('branch_id', $branchId));
            $purchaseItemExists = $purchaseItemExists->where(fn ($query) => $query->where('branch_id', $branchId));
            $returnUnique = $returnUnique->where(fn ($query) => $query->where('branch_id', $branchId));
            $productExists = $productExists->where(fn ($query) => $query->whereNull('deleted_at'));
        }

        return [
            'vendor_id' => ['required', 'uuid', $vendorExists],
            'purchase_id' => ['nullable', 'uuid', $purchaseExists],
            'return_no' => ['required', 'string', 'max:40', $returnUnique],
            'return_date' => ['required', 'date'],
            'status' => ['required', Rule::enum(PurchaseReturnStatus::class)],
            'sub_total' => ['nullable', 'numeric', 'min:0'],
            'tax_total' => ['nullable', 'numeric', 'min:0'],
            'grand_total' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'posted_at' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_item_id' => ['nullable', 'uuid', $purchaseItemExists],
            'items.*.product_id' => ['required', 'uuid', $productExists],
            'items.*.tax_id' => ['nullable', 'uuid', Rule::exists('taxes', 'id')],
            'items.*.qty' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.remarks' => ['nullable', 'string'],
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
                    $validator->errors()->add("items.$index.product_id", 'The product field is required.');
                }
            }
        });
    }
}

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
        $warehouseExists = Rule::exists('warehouses', 'id');
        $purchaseUnique = Rule::unique('purchases', 'purchase_no')->ignore($purchaseId);
        $productExists = Rule::exists('products', 'id');

        if (is_string($branchId) && $branchId !== '') {
            $vendorExists = $vendorExists->where(fn ($query) => $query->where('branch_id', $branchId));
            $warehouseExists = $warehouseExists->where(fn ($query) => $query->where('branch_id', $branchId));
            $purchaseUnique = $purchaseUnique->where(fn ($query) => $query->where('branch_id', $branchId));
            $productExists = $productExists->where(fn ($query) => $query->whereNull('deleted_at'));
        }

        return [
            'warehouse_id' => ['nullable', 'uuid', $warehouseExists],
            'vendor_id' => ['required', 'uuid', $vendorExists],
            'purchase_no' => ['required', 'string', 'max:40', $purchaseUnique],
            'vendor_invoice_no' => ['nullable', 'string', 'max:60'],
            'purchase_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'status' => ['required', Rule::enum(PurchaseStatus::class)],
            'sub_total' => ['nullable', 'numeric', 'min:0'],
            'discount_total' => ['nullable', 'numeric', 'min:0'],
            'tax_total' => ['nullable', 'numeric', 'min:0'],
            'shipping_total' => ['nullable', 'numeric', 'min:0'],
            'grand_total' => ['nullable', 'numeric', 'min:0'],
            'paid_total' => ['nullable', 'numeric', 'min:0'],
            'balance_due' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
            'posted_at' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid', $productExists],
            'items.*.tax_id' => ['nullable', 'uuid', Rule::exists('taxes', 'id')],
            'items.*.qty' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
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
                    $validator->errors()->add(sprintf('items.%s.product_id', $index), 'The product field is required.');
                }
            }
        });
    }
}

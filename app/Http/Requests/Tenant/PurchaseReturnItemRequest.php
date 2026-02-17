<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PurchaseReturnItemRequest extends FormRequest
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

        $purchaseReturnExists = Rule::exists('purchase_returns', 'id');
        $purchaseItemExists = Rule::exists('purchase_items', 'id');

        if (is_string($branchId) && $branchId !== '') {
            $purchaseReturnExists = $purchaseReturnExists->where(fn ($query) => $query->where('branch_id', $branchId));
            $purchaseItemExists = $purchaseItemExists->where(fn ($query) => $query->where('branch_id', $branchId));
        }

        return [
            'purchase_return_id' => ['required', 'uuid', $purchaseReturnExists],
            'purchase_item_id' => ['nullable', 'uuid', $purchaseItemExists],
            'product_id' => ['required', 'uuid', Rule::exists('products', 'id')],
            'tax_id' => ['nullable', 'uuid', Rule::exists('taxes', 'id')],
            'qty' => ['required', 'numeric', 'gt:0'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string'],
        ];
    }
}

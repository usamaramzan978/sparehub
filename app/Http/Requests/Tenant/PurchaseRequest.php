<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\PurchaseStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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

        if (is_string($branchId) && $branchId !== '') {
            $vendorExists = $vendorExists->where(fn ($query) => $query->where('branch_id', $branchId));
            $warehouseExists = $warehouseExists->where(fn ($query) => $query->where('branch_id', $branchId));
            $purchaseUnique = $purchaseUnique->where(fn ($query) => $query->where('branch_id', $branchId));
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
        ];
    }
}

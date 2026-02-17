<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\PurchaseReturnStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $returnUnique = Rule::unique('purchase_returns', 'return_no')->ignore($purchaseReturnId);

        if (is_string($branchId) && $branchId !== '') {
            $vendorExists = $vendorExists->where(fn ($query) => $query->where('branch_id', $branchId));
            $purchaseExists = $purchaseExists->where(fn ($query) => $query->where('branch_id', $branchId));
            $returnUnique = $returnUnique->where(fn ($query) => $query->where('branch_id', $branchId));
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
        ];
    }
}

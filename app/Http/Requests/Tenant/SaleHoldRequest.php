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
}

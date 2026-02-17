<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\PaymentMethodType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class VendorPaymentRequest extends FormRequest
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
        $vendorPaymentId = $this->route('vendor_payment')?->id;
        $branchId = session('tenant.current_branch_id');

        $vendorExists = Rule::exists('vendors', 'id');
        $purchaseExists = Rule::exists('purchases', 'id');
        $paymentUnique = Rule::unique('vendor_payments', 'payment_no')->ignore($vendorPaymentId);

        if (is_string($branchId) && $branchId !== '') {
            $vendorExists = $vendorExists->where(fn ($query) => $query->where('branch_id', $branchId));
            $purchaseExists = $purchaseExists->where(fn ($query) => $query->where('branch_id', $branchId));
            $paymentUnique = $paymentUnique->where(fn ($query) => $query->where('branch_id', $branchId));
        }

        return [
            'vendor_id' => ['required', 'uuid', $vendorExists],
            'purchase_id' => ['nullable', 'uuid', $purchaseExists],
            'payment_no' => ['required', 'string', 'max:40', $paymentUnique],
            'payment_method' => ['required', Rule::enum(PaymentMethodType::class)],
            'amount' => ['required', 'numeric', 'gt:0'],
            'reference_no' => ['nullable', 'string', 'max:60'],
            'paid_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}

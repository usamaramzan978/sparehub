<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\PaymentMethodType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SalePaymentRequest extends FormRequest
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
        $receiverExists = Rule::exists('users', 'id');

        if (is_string($branchId) && $branchId !== '') {
            $saleExists = $saleExists->where(fn ($query) => $query->where('branch_id', $branchId));
            $receiverExists = $receiverExists->where(fn ($query) => $query->where('branch_id', $branchId));
        }

        return [
            'sale_id' => ['required', 'uuid', $saleExists],
            'received_by' => ['nullable', 'uuid', $receiverExists],
            'payment_method' => ['required', Rule::enum(PaymentMethodType::class)],
            'amount' => ['required', 'numeric', 'gt:0'],
            'reference_no' => ['nullable', 'string', 'max:60'],
            'paid_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\SaleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PosStoreRequest extends FormRequest
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
        $customerExists = Rule::exists('customers', 'id');

        if (is_string($branchId) && $branchId !== '') {
            $customerExists = $customerExists->where(fn ($query) => $query->where('branch_id', $branchId));
        }

        return [
            'customer_id' => ['nullable', 'uuid', $customerExists],
            'status' => ['required', Rule::in([
                SaleStatus::POSTED->value,
                SaleStatus::DRAFT->value,
                SaleStatus::HOLD->value,
            ])],
            'discount_type' => ['nullable', Rule::in(['amount', 'percent'])],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'payment_mode' => ['nullable', Rule::in(['cash', 'online', 'debit'])],
            'payment_proof' => [Rule::requiredIf($this->string('payment_mode')->toString() === 'online'), 'nullable', 'image', 'max:5120'],
            'cash_received' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.type' => ['required', Rule::in(['product', 'service'])],
            'items.*.ref_id' => ['required', 'uuid'],
            'items.*.qty' => ['required', 'numeric', 'gt:0'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_inclusive' => ['nullable', 'boolean'],
            'items.*.name' => ['nullable', 'string', 'max:200'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Please select a sale status.',
            'status.in' => 'Selected sale status is invalid.',
            'payment_mode.in' => 'Selected payment mode is invalid.',
            'payment_proof.required' => 'Payment proof is required for online payment.',
            'payment_proof.image' => 'Payment proof must be an image.',
            'items.required' => 'At least one POS item is required.',
            'items.array' => 'Items payload is invalid.',
            'items.min' => 'At least one POS item is required.',
            'items.*.type.required' => 'Line item type is required.',
            'items.*.type.in' => 'Line item type must be product or service.',
            'items.*.ref_id.required' => 'Item reference is required.',
            'items.*.ref_id.uuid' => 'Item reference is invalid.',
            'items.*.qty.required' => 'Quantity is required.',
            'items.*.qty.gt' => 'Quantity must be greater than zero.',
            'items.*.price.required' => 'Price is required.',
        ];
    }
}

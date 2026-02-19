<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\SaleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
        $mechanicExists = Rule::exists('users', 'id');

        if (is_string($branchId) && $branchId !== '') {
            $customerExists = $customerExists->where(fn ($query) => $query->where('branch_id', $branchId));
            $mechanicExists = $mechanicExists->where(fn ($query) => $query->where('branch_id', $branchId));
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
            'items.*.mechanic_enabled' => ['nullable', 'boolean'],
            'items.*.mechanic_id' => ['nullable', 'uuid', $mechanicExists],
            'items.*.mechanic_charge' => ['nullable', 'numeric', 'min:0'],
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
                if (! is_array($item)) {
                    continue;
                }

                $mechanicEnabled = filter_var($item['mechanic_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
                if (! $mechanicEnabled) {
                    continue;
                }

                $mechanicId = $item['mechanic_id'] ?? null;
                if (! is_string($mechanicId) || $mechanicId === '') {
                    $validator->errors()->add("items.$index.mechanic_id", 'Please select a mechanic when Add Mechanic is enabled.');
                }

                $mechanicCharge = $item['mechanic_charge'] ?? null;
                if (! is_numeric($mechanicCharge) || (float) $mechanicCharge <= 0) {
                    $validator->errors()->add("items.$index.mechanic_charge", 'Please enter mechanic payable greater than zero when Add Mechanic is enabled.');
                }
            }
        });
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
            'items.*.mechanic_id.uuid' => 'Selected mechanic is invalid.',
            'items.*.mechanic_charge.numeric' => 'Mechanic payable must be a valid number.',
        ];
    }
}

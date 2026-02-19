<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class ProductStockAdjustmentRequest extends FormRequest
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
        return [
            'product_id' => [
                'required',
                'uuid',
                Rule::exists('products', 'id')->where(fn ($query) => $query->where('track_stock', true)),
            ],
            'action' => ['required', Rule::in(['in', 'out', 'set'])],
            'qty' => ['required', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $action = (string) $this->input('action');
            $qty = (float) $this->input('qty');

            if (in_array($action, ['in', 'out'], true) && $qty <= 0) {
                $validator->errors()->add('qty', 'Quantity must be greater than zero for stock in/out.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_id.required' => 'Please select a product.',
            'product_id.exists' => 'Selected product is invalid or does not track stock.',
            'action.required' => 'Please select adjustment action.',
            'action.in' => 'Selected adjustment action is invalid.',
            'qty.required' => 'Please enter quantity.',
            'qty.min' => 'Quantity cannot be negative.',
        ];
    }
}

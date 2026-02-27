<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ProductRequest extends FormRequest
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
        $productId = $this->route('product')?->id;

        return [
            'name' => ['required', 'string', 'max:180'],
            'category_id' => [
                'nullable',
                'uuid',
                Rule::exists('categories', 'id'),
            ],
            'brand_id' => [
                'nullable',
                'uuid',
                Rule::exists('brands', 'id'),
            ],
            'default_tax_id' => [
                'nullable',
                'uuid',
                Rule::exists('taxes', 'id'),
            ],
            'default_unit_id' => [
                'nullable',
                'uuid',
                Rule::exists('units', 'id'),
            ],
            'sku' => [
                'required',
                'string',
                'max:60',
                Rule::unique('products', 'sku')->ignore($productId),
            ],
            'part_number' => ['nullable', 'string', 'max:60', Rule::unique('products', 'part_number')->ignore($productId)],
            'barcode' => ['nullable', 'string', 'max:80', Rule::unique('products', 'barcode')->ignore($productId)],
            'qrcode' => ['nullable', 'string', 'max:80', Rule::unique('products', 'qrcode')->ignore($productId)],
            'track_stock' => ['sometimes', 'boolean'],
            'opening_stock' => ['nullable', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'mrp' => ['nullable', 'numeric', 'min:0', 'gte:cost'],
            'retail_price' => ['nullable', 'numeric', 'min:0', 'gte:cost', 'lte:mrp'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0', 'gte:cost', 'lte:retail_price'],
            'effective_from' => ['nullable', 'date'],
            'status' => ['required', Rule::enum(RecordStatus::class)],
            'description' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Product name is required.',
            'sku.required' => 'SKU is required.',
            'sku.unique' => 'This SKU already exists.',
            'part_number.unique' => 'This part number already exists.',
            'barcode.unique' => 'This barcode already exists.',
            'qrcode.unique' => 'This QR code already exists.',
            'status.required' => 'Please select a product status.',
            'cost.min' => 'Cost must be zero or greater.',
            'mrp.min' => 'MRP must be zero or greater.',
            'mrp.gte' => 'MRP must be greater than or equal to cost.',
            'retail_price.min' => 'Retail price must be zero or greater.',
            'retail_price.gte' => 'Retail price must be greater than or equal to cost.',
            'retail_price.lte' => 'Retail price must be less than or equal to MRP.',
            'wholesale_price.min' => 'Wholesale price must be zero or greater.',
            'wholesale_price.gte' => 'Wholesale price must be greater than or equal to cost.',
            'wholesale_price.lte' => 'Wholesale price must be less than or equal to retail price.',
        ];
    }
}

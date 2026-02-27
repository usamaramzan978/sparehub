<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\SaleLineType;
use App\Enums\SaleReturnStatus;
use App\Models\SaleReturnItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class SaleReturnRequest extends FormRequest
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
        $saleReturnId = $this->route('sale_return')?->id;
        $branchId = session('tenant.current_branch_id');

        $customerExists = Rule::exists('customers', 'id');
        $saleExists = Rule::exists('sales', 'id');
        $saleItemExists = Rule::exists('sale_items', 'id');
        $returnUnique = Rule::unique('sale_returns', 'return_no')->ignore($saleReturnId);
        $productExists = Rule::exists('products', 'id');

        if (is_string($branchId) && $branchId !== '') {
            $customerExists = $customerExists->where(fn ($query) => $query->where('branch_id', $branchId));
            $saleExists = $saleExists->where(fn ($query) => $query->where('branch_id', $branchId));
            $saleItemExists = $saleItemExists->where(fn ($query) => $query->where('branch_id', $branchId)->where('line_type', SaleLineType::PRODUCT->value));
            $returnUnique = $returnUnique->where(fn ($query) => $query->where('branch_id', $branchId));
            $productExists = $productExists->where(fn ($query) => $query->whereNull('deleted_at'));
        }

        return [
            'customer_id' => ['nullable', 'uuid', $customerExists],
            'sale_id' => ['nullable', 'uuid', $saleExists],
            'return_no' => ['required', 'string', 'max:40', $returnUnique],
            'return_date' => ['required', 'date'],
            'status' => ['required', Rule::enum(SaleReturnStatus::class)],
            'sub_total' => ['nullable', 'numeric', 'min:0'],
            'tax_total' => ['nullable', 'numeric', 'min:0'],
            'grand_total' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'posted_at' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sale_item_id' => ['nullable', 'uuid', $saleItemExists],
            'items.*.product_id' => ['required', 'uuid', $productExists],
            'items.*.tax_id' => ['nullable', 'uuid', Rule::exists('taxes', 'id')],
            'items.*.qty' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.remarks' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $items = $this->input('items', []);
            if (! is_array($items)) {
                return;
            }

            $currentSaleReturnId = (string) ($this->route('sale_return')?->id ?? '');

            foreach ($items as $index => $item) {
                if (! is_array($item) || empty($item['product_id'])) {
                    $validator->errors()->add(sprintf('items.%s.product_id', $index), 'The product field is required.');

                    continue;
                }

                $saleItemId = Arr::get($item, 'sale_item_id');
                if (! is_string($saleItemId) || $saleItemId === '') {
                    continue;
                }

                $soldQty = (float) \App\Models\SaleItem::query()
                    ->whereKey($saleItemId)
                    ->value('qty');

                if ($soldQty <= 0) {
                    continue;
                }

                $alreadyReturnedQty = (float) SaleReturnItem::query()
                    ->where('sale_item_id', $saleItemId)
                    ->whereHas('saleReturn', function ($query) use ($currentSaleReturnId): void {
                        if ($currentSaleReturnId !== '') {
                            $query->whereKeyNot($currentSaleReturnId);
                        }
                    })
                    ->sum('qty');

                $requestedQty = (float) Arr::get($item, 'qty', 0);
                $availableQty = max($soldQty - $alreadyReturnedQty, 0);

                if ($requestedQty > $availableQty) {
                    $validator->errors()->add(
                        sprintf('items.%s.qty', $index),
                        sprintf('Return qty cannot exceed available sold qty (%.3f).', $availableQty)
                    );
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
            'required' => 'The :attribute field is required.',
            'uuid' => 'The :attribute must be a valid UUID.',
            'date' => 'The :attribute must be a valid date.',
            'numeric' => 'The :attribute must be a valid number.',
            'min' => 'The :attribute is below the minimum allowed value.',
            'max' => 'The :attribute exceeds the maximum allowed value.',
            'exists' => 'The selected :attribute is invalid.',
            'unique' => 'The :attribute has already been taken.',
        ];
    }
}

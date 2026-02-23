<?php

declare(strict_types=1);

namespace App\Actions\Tenant\CodeGenerator;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class ResolveCodePayloadAction
{
    /**
     * @return array<string, mixed>|null
     */
    public function handle(Request $request): ?array
    {
        $data = $request->validate([
            'type' => ['nullable', 'in:barcode,qr'],
            'product_id' => ['nullable', 'uuid', Rule::exists('products', 'id')],
            'value' => ['nullable', 'string', 'max:128'],
            'label' => ['nullable', 'string', 'max:120'],
            'format' => ['nullable', 'in:C128,C39,EAN13,EAN8,UPC'],
            'scale' => ['nullable', 'integer', 'min:1', 'max:6'],
            'height' => ['nullable', 'integer', 'min:20', 'max:200'],
            'size' => ['nullable', 'integer', 'min:120', 'max:600'],
            'margin' => ['nullable', 'integer', 'min:0', 'max:20'],
            'qty' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $product = null;
        if (! empty($data['product_id'])) {
            $product = Product::query()->find($data['product_id']);
        }

        $type = $data['type'] ?? 'barcode';
        $fallbackValue = $type === 'qr'
            ? ($product?->qrcode ?? $product?->barcode ?? $product?->sku)
            : ($product?->barcode ?? $product?->sku);
        $value = $data['value'] ?? $fallbackValue;
        if (! $value) {
            return null;
        }

        $label = $data['label'] ?? $product?->name ?? $value;

        return [
            'type' => $type,
            'product_id' => $product?->id,
            'value' => $value,
            'label' => $label,
            'format' => $data['format'] ?? 'C128',
            'scale' => $data['scale'] ?? 2,
            'height' => $data['height'] ?? 80,
            'size' => $data['size'] ?? 240,
            'margin' => $data['margin'] ?? 10,
            'qty' => $data['qty'] ?? 1,
        ];
    }
}

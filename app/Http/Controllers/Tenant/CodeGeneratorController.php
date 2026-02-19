<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Picqer\Barcode\BarcodeGeneratorPNG;

final class CodeGeneratorController extends Controller
{
    public function index(Request $request): View
    {
        $tenantKey = $request->route('tenant') ?? (function_exists('tenant') ? tenant()?->getTenantKey() : null);
        $payload = $this->resolvePayload($request) ?? [];
        $products = Product::query()
            ->where(function ($query): void {
                $query->whereNotNull('barcode')->orWhereNotNull('qrcode');
            })
            ->orderBy('name')
            ->limit(200)
            ->get();

        return view('tenants.codes.index', [
            'products' => $products,
            'payload' => $payload,
            'previewUrl' => $payload !== [] ? route('tenant.codes.render', array_filter([
                'tenant' => $tenantKey,
            ] + $payload)) : null,
            'printUrl' => $payload !== [] ? route('tenant.codes.print', array_filter([
                'tenant' => $tenantKey,
            ] + $payload)) : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $this->resolvePayload($request);
        if (! $payload) {
            return to_route('tenant.codes.index');
        }

        return to_route('tenant.codes.index', array_filter($payload));
    }

    public function render(Request $request): Response
    {
        $payload = $this->resolvePayload($request);
        abort_unless($payload, 404);

        if ($payload['type'] === 'qr') {
            $qrCode = new QrCode(
                $payload['value'],
                size: (int) $payload['size'],
                margin: (int) $payload['margin']
            );

            $writer = new PngWriter();
            $result = $writer->write($qrCode);

            return response($result->getString(), 200, [
                'Content-Type' => 'image/png',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            ]);
        }

        $generator = new BarcodeGeneratorPNG();
        $barcode = $generator->getBarcode(
            $payload['value'],
            $this->barcodeType($payload['format']),
            (int) $payload['scale'],
            (int) $payload['height']
        );

        return response($barcode, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    public function print(Request $request): View
    {
        $payload = $this->resolvePayload($request);
        abort_unless($payload, 404);

        $tenantKey = $request->route('tenant') ?? (function_exists('tenant') ? tenant()?->getTenantKey() : null);

        return view('tenants.codes.print', [
            'payload' => $payload,
            'previewUrl' => route('tenant.codes.render', array_filter([
                'tenant' => $tenantKey,
            ] + $payload)),
        ]);
    }

    public function destroy(): RedirectResponse
    {
        return to_route('tenant.codes.index');
    }

    public function updateBarcode(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'value' => [
                'required',
                'string',
                'max:80',
                Rule::unique('products', 'barcode')->ignore($product->id),
            ],
        ]);

        $product->update([
            'barcode' => $validated['value'],
        ]);

        return back()->with('status', 'Barcode updated.');
    }

    public function deleteBarcode(Product $product): RedirectResponse
    {
        $product->update([
            'barcode' => null,
        ]);

        return back()->with('status', 'Barcode deleted.');
    }

    public function updateQr(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'value' => [
                'required',
                'string',
                'max:80',
                Rule::unique('products', 'qrcode')->ignore($product->id),
            ],
        ]);

        $product->update([
            'qrcode' => $validated['value'],
        ]);

        return back()->with('status', 'QR code updated.');
    }

    public function deleteQr(Product $product): RedirectResponse
    {
        $product->update([
            'qrcode' => null,
        ]);

        return back()->with('status', 'QR code deleted.');
    }

    private function resolvePayload(Request $request): ?array
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

        /** @var Product|null $product */
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

        $label = $data['label']
            ?? $product?->name
            ?? $value;

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

    private function barcodeType(string $format): string
    {
        return match ($format) {
            'C39' => BarcodeGeneratorPNG::TYPE_CODE_39,
            'EAN13' => BarcodeGeneratorPNG::TYPE_EAN_13,
            'EAN8' => BarcodeGeneratorPNG::TYPE_EAN_8,
            'UPC' => BarcodeGeneratorPNG::TYPE_UPC_A,
            default => BarcodeGeneratorPNG::TYPE_CODE_128,
        };
    }
}

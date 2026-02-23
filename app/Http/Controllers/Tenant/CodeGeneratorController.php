<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\CodeGenerator\DeleteProductBarcodeAction;
use App\Actions\Tenant\CodeGenerator\DeleteProductQrAction;
use App\Actions\Tenant\CodeGenerator\RenderCodeImageAction;
use App\Actions\Tenant\CodeGenerator\ResolveCodePayloadAction;
use App\Actions\Tenant\CodeGenerator\UpdateProductBarcodeAction;
use App\Actions\Tenant\CodeGenerator\UpdateProductQrAction;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

final class CodeGeneratorController extends Controller
{
    public function index(Request $request, ResolveCodePayloadAction $resolveCodePayloadAction): View
    {
        $tenantKey = $request->route('tenant') ?? (function_exists('tenant') ? tenant()?->getTenantKey() : null);
        $payload = $resolveCodePayloadAction->handle($request) ?? [];
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

    public function store(Request $request, ResolveCodePayloadAction $resolveCodePayloadAction): RedirectResponse
    {
        $payload = $resolveCodePayloadAction->handle($request);
        if (! $payload) {
            return to_route('tenant.codes.index');
        }

        return to_route('tenant.codes.index', array_filter($payload));
    }

    public function render(Request $request, ResolveCodePayloadAction $resolveCodePayloadAction, RenderCodeImageAction $renderCodeImageAction): Response
    {
        $payload = $resolveCodePayloadAction->handle($request);
        abort_unless($payload, 404);

        $image = $renderCodeImageAction->handle($payload);

        return response($image, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    public function print(Request $request, ResolveCodePayloadAction $resolveCodePayloadAction): View
    {
        $payload = $resolveCodePayloadAction->handle($request);
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

    public function updateBarcode(Request $request, Product $product, UpdateProductBarcodeAction $updateProductBarcodeAction): RedirectResponse
    {
        $validated = $request->validate([
            'value' => [
                'required',
                'string',
                'max:80',
                Rule::unique('products', 'barcode')->ignore($product->id),
            ],
        ]);

        $updateProductBarcodeAction->handle($product, (string) $validated['value']);

        return back()->with('status', 'Barcode updated.');
    }

    public function deleteBarcode(Product $product, DeleteProductBarcodeAction $deleteProductBarcodeAction): RedirectResponse
    {
        $deleteProductBarcodeAction->handle($product);

        return back()->with('status', 'Barcode deleted.');
    }

    public function updateQr(Request $request, Product $product, UpdateProductQrAction $updateProductQrAction): RedirectResponse
    {
        $validated = $request->validate([
            'value' => [
                'required',
                'string',
                'max:80',
                Rule::unique('products', 'qrcode')->ignore($product->id),
            ],
        ]);

        $updateProductQrAction->handle($product, (string) $validated['value']);

        return back()->with('status', 'QR code updated.');
    }

    public function deleteQr(Product $product, DeleteProductQrAction $deleteProductQrAction): RedirectResponse
    {
        $deleteProductQrAction->handle($product);

        return back()->with('status', 'QR code deleted.');
    }
}

@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Catalog')],
            ['label' => __('Products'), 'url' => route('tenant.products.index')],
            ['label' => __('Edit')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Edit Product') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.products.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('tenant.products.update', $product) }}">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="sku">{{ __('SKU') }}</label>
                        <input type="text" name="sku" id="sku"
                            class="form-control @error('sku') is-invalid @enderror" value="{{ old('sku', $product->sku) }}"
                            required>
                        @error('sku')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-5 mb-3">
                        <label class="form-label" for="name">{{ __('Name') }}</label>
                        <input type="text" name="name" id="name"
                            class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $product->name) }}" required>
                        @error('name')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="status">{{ __('Status') }}</label>
                        <select name="status" id="status"
                            class="form-select singl-select-2 @error('status') is-invalid @enderror" required>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" @selected(old('status', $product->status->value) === $status->value)>
                                    {{ ucfirst($status->value) }}</option>
                            @endforeach
                        </select>
                        @error('status')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="part_number">{{ __('Part Number') }}</label>
                        <input type="text" name="part_number" id="part_number"
                            class="form-control @error('part_number') is-invalid @enderror"
                            value="{{ old('part_number', $product->part_number) }}">
                        @error('part_number')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="barcode">{{ __('Barcode') }}</label>
                        <input type="text" name="barcode" id="barcode"
                            class="form-control @error('barcode') is-invalid @enderror"
                            value="{{ old('barcode', $product->barcode) }}">
                        @error('barcode')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="qrcode">{{ __('QR Code') }}</label>
                        <input type="text" name="qrcode" id="qrcode"
                            class="form-control @error('qrcode') is-invalid @enderror"
                            value="{{ old('qrcode', $product->qrcode) }}">
                        @error('qrcode')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="default_unit_id">{{ __('Default Unit') }}</label>
                        <select name="default_unit_id" id="default_unit_id"
                            class="form-select singl-select-2 @error('default_unit_id') is-invalid @enderror">
                            <option value="">{{ __('None') }}</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}" @selected(old('default_unit_id', $product->default_unit_id) === $unit->id)>{{ $unit->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('default_unit_id')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="category_id">{{ __('Category') }}</label>
                        <select name="category_id" id="category_id"
                            class="form-select singl-select-2 @error('category_id') is-invalid @enderror">
                            <option value="">{{ __('None') }}</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) === $category->id)>{{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="brand_id">{{ __('Brand') }}</label>
                        <select name="brand_id" id="brand_id"
                            class="form-select singl-select-2 @error('brand_id') is-invalid @enderror">
                            <option value="">{{ __('None') }}</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}" @selected(old('brand_id', $product->brand_id) === $brand->id)>{{ $brand->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('brand_id')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="default_tax_id">{{ __('Default Tax') }}</label>
                        <select name="default_tax_id" id="default_tax_id"
                            class="form-select singl-select-2 @error('default_tax_id') is-invalid @enderror">
                            <option value="">{{ __('None') }}</option>
                            @foreach ($taxes as $tax)
                                <option value="{{ $tax->id }}" @selected(old('default_tax_id', $product->default_tax_id) === $tax->id)>
                                    {{ $tax->name }} ({{ $tax->rate }}%)
                                </option>
                            @endforeach
                        </select>
                        @error('default_tax_id')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3 form-check">
                        <input type="hidden" name="track_stock" value="0">
                        <input type="checkbox" name="track_stock" id="track_stock"
                            class="form-check-input @error('track_stock') is-invalid @enderror" value="1"
                            @checked(old('track_stock', $product->track_stock))>
                        <label class="form-check-label" for="track_stock">{{ __('Track Stock') }}</label>
                        @error('track_stock')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="opening_stock">{{ __('Current Stock') }}</label>
                        <input type="number" name="opening_stock" id="opening_stock"
                            class="form-control @error('opening_stock') is-invalid @enderror" min="0"
                            step="0.001" value="{{ old('opening_stock', $stockOnHand) }}">
                        <small class="text-muted">{{ __('Set the total stock quantity for this product.') }}</small>
                        @error('opening_stock')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="cost">{{ __('Cost') }}</label>
                        <input type="number" step="0.01" min="0" name="cost" id="cost"
                            class="form-control @error('cost') is-invalid @enderror"
                            value="{{ old('cost', (string) ($latestPrice?->cost ?? '0')) }}">
                        @error('cost')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="mrp">{{ __('MRP') }}</label>
                        <input type="number" step="0.01" min="0" name="mrp" id="mrp"
                            class="form-control @error('mrp') is-invalid @enderror"
                            value="{{ old('mrp', (string) ($latestPrice?->mrp ?? '0')) }}">
                        @error('mrp')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="retail_price">{{ __('Retail Price') }}</label>
                        <input type="number" step="0.01" min="0" name="retail_price" id="retail_price"
                            class="form-control @error('retail_price') is-invalid @enderror"
                            value="{{ old('retail_price', (string) ($latestPrice?->retail_price ?? '0')) }}">
                        @error('retail_price')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="wholesale_price">{{ __('Wholesale Price') }}</label>
                        <input type="number" step="0.01" min="0" name="wholesale_price" id="wholesale_price"
                            class="form-control @error('wholesale_price') is-invalid @enderror"
                            value="{{ old('wholesale_price', (string) ($latestPrice?->wholesale_price ?? '0')) }}">
                        @error('wholesale_price')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label" for="effective_from">{{ __('Price Effective From') }}</label>
                        <input type="datetime-local" name="effective_from" id="effective_from"
                            class="form-control @error('effective_from') is-invalid @enderror"
                            value="{{ old('effective_from', \App\Support\TenantDateTime::format($latestPrice?->effective_from ?? now(), 'Y-m-d\\TH:i', '')) }}">
                        @error('effective_from')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label" for="description">{{ __('Description') }}</label>
                        <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror"
                            rows="4">{{ old('description', $product->description) }}</textarea>
                        @error('description')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
            </form>
        </div>
    </div>

    @php
        $tenantKey = request()->route('tenant') ?? (function_exists('tenant') ? tenant()?->getTenantKey() : null);
        $barcodeValue = old('barcode', $product->barcode);
        $qrValue = old('qrcode', $product->qrcode);
        $barcodeLabel = $barcodeValue;
        $qrLabel = $qrValue;
        $barcodeRenderUrl = $barcodeValue
            ? route('tenant.codes.render', [
                'tenant' => $tenantKey,
                'type' => 'barcode',
                'product_id' => $product->id,
                'value' => $barcodeValue,
                'label' => $barcodeLabel,
                'format' => 'C128',
                'scale' => 2,
                'height' => 80,
                'qty' => 1,
            ])
            : null;
        $barcodePrintUrl = $barcodeValue
            ? route('tenant.codes.print', [
                'tenant' => $tenantKey,
                'type' => 'barcode',
                'product_id' => $product->id,
                'value' => $barcodeValue,
                'label' => $barcodeLabel,
                'format' => 'C128',
                'scale' => 2,
                'height' => 80,
                'qty' => 1,
            ])
            : null;
        $qrRenderUrl = $qrValue
            ? route('tenant.codes.render', [
                'tenant' => $tenantKey,
                'type' => 'qr',
                'product_id' => $product->id,
                'value' => $qrValue,
                'label' => $qrLabel,
                'size' => 240,
                'margin' => 10,
                'qty' => 1,
            ])
            : null;
        $qrPrintUrl = $qrValue
            ? route('tenant.codes.print', [
                'tenant' => $tenantKey,
                'type' => 'qr',
                'product_id' => $product->id,
                'value' => $qrValue,
                'label' => $qrLabel,
                'size' => 240,
                'margin' => 10,
                'qty' => 1,
            ])
            : null;
    @endphp

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-header">
            <h5 class="card-title mb-0">Codes Preview</h5>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-12 col-md-6">
                    <div class="p-3 rounded border h-100">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="mb-0 fw-semibold">Barcode</h6>
                            <div class="d-flex gap-2">
                                @if ($barcodePrintUrl)
                                    <a class="btn btn-sm btn-outline-primary" href="{{ $barcodePrintUrl }}"
                                        target="_blank" rel="noopener">
                                        <i class="ri-printer-line me-1"></i> Print
                                    </a>
                                @endif
                                @if ($barcodeValue)
                                    <form method="POST"
                                        action="{{ route('tenant.products.barcode.delete', ['product' => $product]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                        @if ($barcodeRenderUrl)
                            <div class="text-center">
                                <img src="{{ $barcodeRenderUrl }}" alt="Barcode" class="img-fluid mb-2">
                                <div class="text-muted">{{ $barcodeLabel }}</div>
                            </div>
                        @else
                            <p class="text-muted mb-0">No barcode set for this product.</p>
                        @endif
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="p-3 rounded border h-100">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="mb-0 fw-semibold">QR Code</h6>
                            <div class="d-flex gap-2">
                                @if ($qrPrintUrl)
                                    <a class="btn btn-sm btn-outline-primary" href="{{ $qrPrintUrl }}" target="_blank"
                                        rel="noopener">
                                        <i class="ri-printer-line me-1"></i> Print
                                    </a>
                                @endif
                                @if ($qrValue)
                                    <form method="POST"
                                        action="{{ route('tenant.products.qrcode.delete', ['product' => $product]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                        @if ($qrRenderUrl)
                            <div class="text-center">
                                <img src="{{ $qrRenderUrl }}" alt="QR Code" class="img-fluid mb-2">
                                <div class="text-muted">{{ $qrLabel }}</div>
                            </div>
                        @else
                            <p class="text-muted mb-0">No QR code set for this product.</p>
                        @endif
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="p-3 rounded border h-100">
                        <h6 class="mb-3 fw-semibold">Barcode Settings</h6>
                        <form method="POST" action="{{ route('tenant.products.barcode.update', ['product' => $product]) }}"
                            class="row g-3">
                            @csrf
                            @method('PUT')
                            <div class="col-12">
                                <label class="form-label" for="barcode_value_edit">Value</label>
                                <input type="text" name="value" id="barcode_value_edit" class="form-control"
                                    value="{{ $barcodeValue }}">
                            </div>
                            <div class="col-12 d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-outline-primary">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="p-3 rounded border h-100">
                        <h6 class="mb-3 fw-semibold">QR Settings</h6>
                        <form method="POST" action="{{ route('tenant.products.qrcode.update', ['product' => $product]) }}"
                            class="row g-3">
                            @csrf
                            @method('PUT')
                            <div class="col-12">
                                <label class="form-label" for="qrcode_value_edit">Value</label>
                                <input type="text" name="value" id="qrcode_value_edit" class="form-control"
                                    value="{{ $qrValue }}">
                            </div>
                            <div class="col-12 d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-outline-primary">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

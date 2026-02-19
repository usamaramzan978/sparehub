@extends('layouts.app')

@section('content')
    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-header">
            <h5 class="card-title mb-0">Code Generator</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('tenant.codes.store') }}" class="row g-3 mb-4">
                @csrf
                <div class="col-md-4">
                    <label class="form-label" for="product_id">Product</label>
                    <select name="product_id" id="product_id" class="form-select singl-select-2">
                        <option value="">Select product</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected(($payload['product_id'] ?? null) === $product->id)>
                                {{ $product->name }} ({{ $product->sku }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="type">Type</label>
                    <select name="type" id="type" class="form-select">
                        <option value="barcode" @selected(($payload['type'] ?? 'barcode') === 'barcode')>Barcode</option>
                        <option value="qr" @selected(($payload['type'] ?? null) === 'qr')>QR</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="value">Value</label>
                    <input type="text" name="value" id="value" class="form-control" value="{{ $payload['value'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="label">Label</label>
                    <input type="text" name="label" id="label" class="form-control" value="{{ $payload['label'] ?? '' }}">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Generate</button>
                </div>
            </form>

            @if ($previewUrl)
                <div class="text-center">
                    <img src="{{ $previewUrl }}" alt="Code Preview" class="img-fluid mb-3">
                    @if ($printUrl)
                        <div>
                            <a href="{{ $printUrl }}" target="_blank" rel="noopener"
                                class="btn btn-outline-primary btn-sm">Print</a>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endsection

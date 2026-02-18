@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Catalog')],
            ['label' => __('Product Prices'), 'url' => route('tenant.product-prices.index')],
            ['label' => __('Details')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Product Price Details') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.product-prices.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
                <div>
                    <div class="text-muted small">{{ __('Product') }}</div>
                    <h5 class="mb-1">{{ $productPrice->product?->name ?? '-' }}</h5>
                    <div class="text-muted small">{{ __('SKU') }}: {{ $productPrice->product?->sku ?? '-' }}</div>
                </div>
                <div class="text-end">
                    <div class="text-muted small">{{ __('Branch') }}</div>
                    <div class="fw-semibold">{{ $productPrice->branch?->name ?? '-' }}</div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Cost') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $productPrice->cost, 2) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('MRP') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $productPrice->mrp, 2) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Retail') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $productPrice->retail_price, 2) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Wholesale') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $productPrice->wholesale_price, 2) }}</div>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Effective From') }}</div>
                        <div class="fw-semibold">{{ $productPrice->effective_from?->format('Y-m-d H:i') ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Created At') }}</div>
                        <div class="fw-semibold">{{ $productPrice->created_at?->format('Y-m-d H:i') ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

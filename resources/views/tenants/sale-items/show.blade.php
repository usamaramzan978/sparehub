@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Sales')],
            ['label' => __('Sale Items'), 'url' => route('tenant.sale-items.index')],
            ['label' => __('Details')],
        ];

        $itemName = $saleItem->description ?: $saleItem->product?->name ?? ($saleItem->serviceCatalog?->name ?? '-');
    @endphp

    <x-breadcrumb title="{{ __('Sale Item Details') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.sale-items.edit', $saleItem) }}" class="btn btn-outline-primary">{{ __('Edit') }}</a>
            <a href="{{ route('tenant.sale-items.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
                <div>
                    <div class="text-muted small">{{ __('Item') }}</div>
                    <h5 class="mb-1">{{ $itemName }}</h5>
                    <div class="text-muted small">{{ __('Line Type') }}: {{ ucfirst($saleItem->line_type->value) }}</div>
                </div>
                <div class="text-end">
                    <div class="text-muted small">{{ __('Invoice') }}</div>
                    <div class="fw-semibold">{{ $saleItem->sale?->invoice_no ?? '-' }}</div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Qty') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $saleItem->qty, 3) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Unit Price') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $saleItem->unit_price, 2) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Discount') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $saleItem->discount_amount, 2) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Tax') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $saleItem->tax_amount, 2) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Mechanic Payable') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $saleItem->mechanic_charge, 2) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Line Total') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $saleItem->line_total, 2) }}</div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-4">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Product') }}</div>
                        <div class="fw-semibold">{{ $saleItem->product?->name ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Service Catalog') }}</div>
                        <div class="fw-semibold">{{ $saleItem->serviceCatalog?->name ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Job Card Service') }}</div>
                        <div class="fw-semibold">{{ $saleItem->jobCardService?->name ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Mechanic') }}</div>
                        <div class="fw-semibold">{{ $saleItem->mechanic?->name ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

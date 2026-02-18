@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Purchases')],
            ['label' => __('Purchase Items'), 'url' => route('tenant.purchase-items.index')],
            ['label' => __('Details')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Purchase Item Details') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.purchase-items.edit', $purchaseItem) }}"
                class="btn btn-outline-primary">{{ __('Edit') }}</a>
            <a href="{{ route('tenant.purchase-items.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
                <div>
                    <div class="text-muted small">{{ __('Product') }}</div>
                    <h5 class="mb-1">{{ $purchaseItem->product?->name ?? '-' }}</h5>
                    <div class="text-muted small">{{ __('Purchase') }}: {{ $purchaseItem->purchase?->purchase_no ?? '-' }}
                    </div>
                </div>
                <div class="text-end">
                    <div class="text-muted small">{{ __('Vendor') }}</div>
                    <div class="fw-semibold">{{ $purchaseItem->purchase?->vendor?->name ?? '-' }}</div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-6 col-xl-2">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Qty') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $purchaseItem->qty, 3) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Received') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $purchaseItem->received_qty, 3) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Unit Cost') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $purchaseItem->unit_cost, 2) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Discount') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $purchaseItem->discount_amount, 2) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Tax') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $purchaseItem->tax_amount, 2) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Line Total') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $purchaseItem->line_total, 2) }}</div>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Tax Rule') }}</div>
                        <div class="fw-semibold">{{ $purchaseItem->tax?->name ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Purchase Date') }}</div>
                        <div class="fw-semibold">{{ $purchaseItem->purchase?->purchase_date?->format('Y-m-d') ?? '-' }}
                        </div>
                    </div>
                </div>
            </div>

            @if ($purchaseItem->remarks)
                <div class="border rounded p-3 mt-3">
                    <div class="text-muted small">{{ __('Remarks') }}</div>
                    <div class="fw-semibold">{{ $purchaseItem->remarks }}</div>
                </div>
            @endif
        </div>
    </div>
@endsection

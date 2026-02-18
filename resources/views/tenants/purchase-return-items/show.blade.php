@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Purchases')],
            ['label' => __('Purchase Return Items'), 'url' => route('tenant.purchase-return-items.index')],
            ['label' => __('Details')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Purchase Return Item Details') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.purchase-return-items.edit', $purchaseReturnItem) }}"
                class="btn btn-outline-primary">{{ __('Edit') }}</a>
            <a href="{{ route('tenant.purchase-return-items.index') }}"
                class="btn btn-outline-secondary">{{ __('Back') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
                <div>
                    <div class="text-muted small">{{ __('Product') }}</div>
                    <h5 class="mb-1">{{ $purchaseReturnItem->product?->name ?? '-' }}</h5>
                    <div class="text-muted small">{{ __('Return') }}:
                        {{ $purchaseReturnItem->purchaseReturn?->return_no ?? '-' }}</div>
                </div>
                <div class="text-end">
                    <div class="text-muted small">{{ __('Vendor') }}</div>
                    <div class="fw-semibold">{{ $purchaseReturnItem->purchaseReturn?->vendor?->name ?? '-' }}</div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-6 col-xl-2">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Qty') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $purchaseReturnItem->qty, 3) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Unit Cost') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $purchaseReturnItem->unit_cost, 2) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Tax') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $purchaseReturnItem->tax_amount, 2) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Line Total') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $purchaseReturnItem->line_total, 2) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Source Purchase Item') }}</div>
                        <div class="fw-semibold">{{ $purchaseReturnItem->purchaseItem?->id ?? '-' }}</div>
                    </div>
                </div>
            </div>

            @if ($purchaseReturnItem->remarks)
                <div class="border rounded p-3 mt-3">
                    <div class="text-muted small">{{ __('Remarks') }}</div>
                    <div class="fw-semibold">{{ $purchaseReturnItem->remarks }}</div>
                </div>
            @endif
        </div>
    </div>
@endsection

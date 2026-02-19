@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Sales')],
            ['label' => __('Sale Holds (POS Hold)'), 'url' => route('tenant.sale-holds.index')],
            ['label' => __('Details')],
        ];

        $payloadJson = json_encode(
            $saleHold->payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );
    @endphp

    <x-breadcrumb title="{{ __('Sale Hold Details') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.sale-holds.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
                <div>
                    <div class="text-muted small">{{ __('Hold No') }}</div>
                    <h5 class="mb-1">{{ $saleHold->hold_no }}</h5>
                </div>
                <div class="text-end">
                    <div class="text-muted small">{{ __('Expires At') }}</div>
                    <div class="fw-semibold">{{ $saleHold->expires_at?->format('Y-m-d H:i') ?? '-' }}</div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Customer') }}</div>
                        <div class="fw-semibold">{{ $saleHold->customer?->name ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Created By') }}</div>
                        <div class="fw-semibold">{{ $saleHold->creator?->name ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Created At') }}</div>
                        <div class="fw-semibold">{{ $saleHold->created_at?->format('Y-m-d H:i') ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-header">
            <h6 class="mb-0">{{ __('Hold Payload') }}</h6>
        </div>
        <div class="card-body">
            <pre class="bg-light rounded p-3 mb-0 small" style="white-space: pre-wrap;">{{ $payloadJson }}</pre>
        </div>
    </div>
@endsection

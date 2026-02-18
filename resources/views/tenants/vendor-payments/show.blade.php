@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Purchases')],
            ['label' => __('Vendor Payments'), 'url' => route('tenant.vendor-payments.index')],
            ['label' => __('Details')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Vendor Payment Details') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.vendor-payments.edit', $vendorPayment) }}"
                class="btn btn-outline-primary">{{ __('Edit') }}</a>
            <a href="{{ route('tenant.vendor-payments.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
                <div>
                    <div class="text-muted small">{{ __('Payment No') }}</div>
                    <h5 class="mb-1">{{ $vendorPayment->payment_no }}</h5>
                    <div class="text-muted small">{{ __('Paid At') }}:
                        {{ $vendorPayment->paid_at?->format('Y-m-d H:i') ?? '-' }}</div>
                </div>
                <div class="text-end">
                    <div class="text-muted small">{{ __('Amount') }}</div>
                    <h5 class="mb-1">{{ number_format((float) $vendorPayment->amount, 2) }}</h5>
                    <div class="text-muted small">{{ ucfirst($vendorPayment->payment_method->value) }}</div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Vendor') }}</div>
                        <div class="fw-semibold">{{ $vendorPayment->vendor?->name ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Purchase') }}</div>
                        <div class="fw-semibold">{{ $vendorPayment->purchase?->purchase_no ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Created By') }}</div>
                        <div class="fw-semibold">{{ $vendorPayment->creator?->name ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Reference No') }}</div>
                        <div class="fw-semibold">{{ $vendorPayment->reference_no ?: '-' }}</div>
                    </div>
                </div>
            </div>

            @if ($vendorPayment->notes)
                <div class="border rounded p-3 mt-3">
                    <div class="text-muted small">{{ __('Notes') }}</div>
                    <div class="fw-semibold">{{ $vendorPayment->notes }}</div>
                </div>
            @endif
        </div>
    </div>
@endsection

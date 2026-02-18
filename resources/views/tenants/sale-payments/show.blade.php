@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Sales')],
            ['label' => __('Sale Payments'), 'url' => route('tenant.sale-payments.index')],
            ['label' => __('Details')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Sale Payment Details') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.sale-payments.edit', $salePayment) }}"
                class="btn btn-outline-primary">{{ __('Edit') }}</a>
            <a href="{{ route('tenant.sale-payments.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
                <div>
                    <div class="text-muted small">{{ __('Invoice') }}</div>
                    <h5 class="mb-1">{{ $salePayment->sale?->invoice_no ?? '-' }}</h5>
                    <div class="text-muted small">{{ __('Customer') }}: {{ $salePayment->sale?->customer?->name ?? '-' }}
                    </div>
                </div>
                <div class="text-end">
                    <div class="text-muted small">{{ __('Amount') }}</div>
                    <h5 class="mb-1">{{ number_format((float) $salePayment->amount, 2) }}</h5>
                    <div class="text-muted small">{{ ucfirst($salePayment->payment_method->value) }}</div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Paid At') }}</div>
                        <div class="fw-semibold">{{ $salePayment->paid_at?->format('Y-m-d H:i') ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Received By') }}</div>
                        <div class="fw-semibold">{{ $salePayment->receiver?->name ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Reference No') }}</div>
                        <div class="fw-semibold">{{ $salePayment->reference_no ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Branch') }}</div>
                        <div class="fw-semibold">{{ $salePayment->branch?->name ?? '-' }}</div>
                    </div>
                </div>
            </div>

            @if ($salePayment->notes)
                <div class="border rounded p-3 mt-3">
                    <div class="text-muted small">{{ __('Notes') }}</div>
                    <div class="fw-semibold">{{ $salePayment->notes }}</div>
                </div>
            @endif

            @if ($salePayment->payment_proof_path)
                <div class="border rounded p-3 mt-3">
                    <div class="text-muted small mb-2">{{ __('Payment Proof') }}</div>
                    <a href="{{ asset('storage/' . $salePayment->payment_proof_path) }}" target="_blank" rel="noopener">
                        <img src="{{ asset('storage/' . $salePayment->payment_proof_path) }}"
                            alt="{{ __('Payment Proof') }}" class="img-fluid rounded" style="max-height: 320px;">
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection

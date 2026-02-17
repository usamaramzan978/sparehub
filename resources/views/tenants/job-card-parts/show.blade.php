@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Workshop')],
            ['label' => __('Job Card Parts'), 'url' => route('tenant.job-card-parts.index')],
            ['label' => __('Details')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Job Card Part Details') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.job-card-parts.edit', $partLine) }}" class="btn btn-outline-primary">{{ __('Edit') }}</a>
            <a href="{{ route('tenant.job-card-parts.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
                <div>
                    <div class="text-muted small">{{ __('Product') }}</div>
                    <h5 class="mb-1">{{ $partLine->product?->name ?? '-' }}</h5>
                    <div class="text-muted small">{{ __('Job Card') }}: {{ $partLine->jobCard?->job_no ?? '-' }}</div>
                </div>
                <div class="text-end">
                    <div class="text-muted small">{{ __('Customer') }}</div>
                    <div class="fw-semibold">{{ $partLine->jobCard?->customer?->name ?? '-' }}</div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Vehicle') }}</div>
                        <div class="fw-semibold">{{ $partLine->jobCard?->vehicle?->registration_no ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Qty') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $partLine->qty, 3) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Unit Price') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $partLine->unit_price, 2) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Line Total') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $partLine->line_total, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

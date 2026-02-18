@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Workshop')],
            ['label' => __('Job Card Services'), 'url' => route('tenant.job-card-services.index')],
            ['label' => __('Details')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Job Card Service Details') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.job-card-services.edit', $serviceLine) }}"
                class="btn btn-outline-primary">{{ __('Edit') }}</a>
            <a href="{{ route('tenant.job-card-services.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
                <div>
                    <div class="text-muted small">{{ __('Service') }}</div>
                    <h5 class="mb-1">{{ $serviceLine->service_name }}</h5>
                    <div class="text-muted small">{{ __('Job Card') }}: {{ $serviceLine->jobCard?->job_no ?? '-' }}</div>
                </div>
                <div class="text-end">
                    <div class="text-muted small">{{ __('Status') }}</div>
                    <div class="fw-semibold">{{ ucfirst(str_replace('_', ' ', $serviceLine->status->value)) }}</div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Customer') }}</div>
                        <div class="fw-semibold">{{ $serviceLine->jobCard?->customer?->name ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Vehicle') }}</div>
                        <div class="fw-semibold">{{ $serviceLine->jobCard?->vehicle?->registration_no ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Technician') }}</div>
                        <div class="fw-semibold">{{ $serviceLine->technician?->name ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Service Catalog') }}</div>
                        <div class="fw-semibold">{{ $serviceLine->serviceCatalog?->name ?? '-' }}</div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-4">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Qty') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $serviceLine->qty, 3) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Rate') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $serviceLine->rate, 2) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Line Total') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $serviceLine->line_total, 2) }}</div>
                    </div>
                </div>
            </div>

            @if ($serviceLine->remarks)
                <div class="border rounded p-3 mt-3">
                    <div class="text-muted small">{{ __('Remarks') }}</div>
                    <div class="fw-semibold">{{ $serviceLine->remarks }}</div>
                </div>
            @endif
        </div>
    </div>
@endsection

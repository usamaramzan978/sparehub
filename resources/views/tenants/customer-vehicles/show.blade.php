@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('People')],
            ['label' => __('Customer Vehicles'), 'url' => route('tenant.customer-vehicles.index')],
            ['label' => __('Details')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Customer Vehicle Details') }}" :items="$breadcrumbs">
        <x-slot:actions>
            @if ($vehicle->customer)
                <a href="{{ route('tenant.customers.show', $vehicle->customer) }}"
                    class="btn btn-outline-primary">{{ __('Customer') }}</a>
            @endif
            <a href="{{ route('tenant.customer-vehicles.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card h-100">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted small">{{ __('Registration No') }}</div>
                            <div class="fw-semibold">{{ $vehicle->registration_no }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">{{ __('Customer') }}</div>
                            <div class="fw-semibold">{{ $vehicle->customer?->name ?? '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">{{ __('Model') }}</div>
                            <div class="fw-semibold">{{ $vehicle->model ?: '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">{{ __('Year') }}</div>
                            <div class="fw-semibold">{{ $vehicle->year ?: '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">{{ __('Chassis No') }}</div>
                            <div class="fw-semibold">{{ $vehicle->chassis_no ?: '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">{{ __('Engine No') }}</div>
                            <div class="fw-semibold">{{ $vehicle->engine_no ?: '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">{{ __('Meter Reading') }}</div>
                            <div class="fw-semibold">{{ number_format((float) $vehicle->meter_reading, 3) }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">{{ __('Created At') }}</div>
                            <div class="fw-semibold">{{ $vehicle->created_at?->format('Y-m-d H:i') ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

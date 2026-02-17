@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('People')],
            ['label' => __('Vendors'), 'url' => route('tenant.vendors.index')],
            ['label' => __('Details')],
        ];

        $statusClass = $vendor->status->value === 'active' ? 'bg-success-transparent' : 'bg-secondary-transparent';
        $vendorRows = [
            ['label' => __('Code'), 'value' => $vendor->code],
            ['label' => __('Branch'), 'value' => $vendor->branch?->name],
            ['label' => __('Phone'), 'value' => $vendor->phone ?: null],
            ['label' => __('Email'), 'value' => $vendor->email ?: null],
            ['label' => __('CNIC'), 'value' => $vendor->cnic ?: null],
            ['label' => __('NTN'), 'value' => $vendor->ntn ?: null],
            ['label' => __('City'), 'value' => $vendor->city ?: null],
            ['label' => __('Opening Balance'), 'value' => number_format((float) $vendor->opening_balance, 2)],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Vendor Details') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.vendors.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
                <div>
                    <div class="text-muted small">{{ __('Vendor') }}</div>
                    <h5 class="mb-1">{{ $vendor->name }}</h5>
                </div>
                <span class="badge {{ $statusClass }}">{{ ucfirst($vendor->status->value) }}</span>
            </div>

            <div class="row g-3">
                @foreach ($vendorRows as $vendorRow)
                    @if ($vendorRow['value'] !== null && $vendorRow['value'] !== '')
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">{{ $vendorRow['label'] }}</div>
                                <div class="fw-semibold">{{ $vendorRow['value'] }}</div>
                            </div>
                        </div>
                    @endif
                @endforeach

                @if ($vendor->address)
                    <div class="col-12">
                        <div class="border rounded p-3">
                            <div class="text-muted small">{{ __('Address') }}</div>
                            <div class="fw-semibold">{{ $vendor->address }}</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

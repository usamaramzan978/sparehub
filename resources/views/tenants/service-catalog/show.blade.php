@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Catalog')],
            ['label' => __('Service Catalog'), 'url' => route('tenant.service-catalog.index')],
            ['label' => __('Details')],
        ];

        $statusClass =
            $serviceCatalog->status->value === 'active' ? 'bg-success-transparent' : 'bg-secondary-transparent';

        $metaRows = [
            ['label' => __('Code'), 'value' => $serviceCatalog->code],
            ['label' => __('Type'), 'value' => ucfirst($serviceCatalog->type->value)],
            ['label' => __('Branch'), 'value' => $serviceCatalog->branch?->name],
            ['label' => __('Duration (Min)'), 'value' => $serviceCatalog->duration_minutes],
            [
                'label' => __('Tax Rule'),
                'value' => $serviceCatalog->defaultTax
                    ? $serviceCatalog->defaultTax->name . ' (' . $serviceCatalog->defaultTax->rate . '%)'
                    : null,
            ],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Service Details') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.service-catalog.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
                <div>
                    <div class="text-muted small">{{ __('Service') }}</div>
                    <h5 class="mb-1">{{ $serviceCatalog->name }}</h5>
                    <div class="text-muted small">{{ __('Base Price') }}:
                        {{ number_format((float) $serviceCatalog->base_price, 2) }}</div>
                </div>
                <span class="badge {{ $statusClass }}">{{ ucfirst($serviceCatalog->status->value) }}</span>
            </div>

            <div class="row g-3">
                @foreach ($metaRows as $metaRow)
                    @if ($metaRow['value'] !== null && $metaRow['value'] !== '')
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">{{ $metaRow['label'] }}</div>
                                <div class="fw-semibold">{{ $metaRow['value'] }}</div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
@endsection

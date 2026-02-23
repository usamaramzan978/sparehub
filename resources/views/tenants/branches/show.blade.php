@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Operations')],
            ['label' => __('Branches'), 'url' => route('tenant.branches.index')],
            ['label' => __('Details')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Branch Details') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.branches.edit', $branch) }}" class="btn btn-secondary">{{ __('Edit') }}</a>
            <a href="{{ route('tenant.branches.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    @php
        $status = $branch->status->value;
        $statusClasses = [
            'active' => 'bg-success-transparent',
            'inactive' => 'bg-secondary-transparent',
        ];
        $statusClass = $statusClasses[$status] ?? 'bg-secondary-transparent';
    @endphp

    <div class="row g-3">
        <div class="col-12 col-lg-7">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <div>
                            <div class="text-muted small">{{ __('Branch') }}</div>
                            <div class="fw-semibold fs-5">{{ $branch->name }}</div>
                            <div class="text-muted small">{{ __('Code') }}</div>
                            <div class="fw-semibold">{{ $branch->code }}</div>
                        </div>
                        <span class="badge {{ $statusClass }}">{{ ucfirst($status) }}</span>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="text-muted small">{{ __('Warehouse') }}</div>
                            <div class="fw-semibold">{{ $branch->warehouse?->name ?? '-' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small">{{ __('Created At') }}</div>
                            <div class="fw-semibold">@tenantDate($branch->created_at, 'Y-m-d H:i', '')</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small">{{ __('Updated At') }}</div>
                            <div class="fw-semibold">@tenantDate($branch->updated_at, 'Y-m-d H:i', '')</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-header">
                    <h6 class="mb-0">{{ __('Branch Snapshot') }}</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">{{ __('Status') }}</span>
                        <span class="fw-semibold">{{ ucfirst($status) }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">{{ __('Warehouse') }}</span>
                        <span class="fw-semibold">{{ $branch->warehouse?->name ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

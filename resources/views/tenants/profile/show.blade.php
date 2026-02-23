@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Account')],
            ['label' => __('Profile')],
        ];
        $status = $user->status->value;
        $statusClasses = [
            'active' => 'bg-success-transparent',
            'suspended' => 'bg-warning-transparent',
            'inactive' => 'bg-secondary-transparent',
        ];
        $statusClass = $statusClasses[$status] ?? 'bg-secondary-transparent';
        $roles = method_exists($user, 'getRoleNames') ? $user->getRoleNames()->implode(', ') : null;
    @endphp

    <x-breadcrumb title="{{ __('Profile') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.profile.security.show') }}" class="btn btn-outline-primary">{{ __('Security') }}</a>
            <a href="{{ route('tenant.profile.edit') }}" class="btn btn-secondary">{{ __('Edit Profile') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="row g-3">
        <div class="col-12 col-lg-7">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <div>
                            <div class="text-muted small">{{ __('User') }}</div>
                            <div class="fw-semibold fs-5">{{ $user->name }}</div>
                            <div class="text-muted small">{{ __('Email') }}</div>
                            <div class="fw-semibold">{{ $user->email }}</div>
                        </div>
                        <span class="badge {{ $statusClass }}">{{ ucfirst($status) }}</span>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="text-muted small">{{ __('Phone') }}</div>
                            <div class="fw-semibold">{{ $user->phone ?? '-' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small">{{ __('Branch') }}</div>
                            <div class="fw-semibold">{{ $user->branch?->name ?? '-' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small">{{ __('Role') }}</div>
                            <div class="fw-semibold">{{ $roles ?: '-' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small">{{ __('Created At') }}</div>
                            <div class="fw-semibold">@tenantDate($user->created_at, 'Y-m-d H:i', '')</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small">{{ __('Updated At') }}</div>
                            <div class="fw-semibold">@tenantDate($user->updated_at, 'Y-m-d H:i', '')</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0">{{ __('Activity Snapshot') }}</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">{{ __('Opened Sessions') }}</span>
                        <span class="fw-semibold">{{ number_format((float) ($stats['opened_sessions'] ?? 0), 0) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">{{ __('Closed Sessions') }}</span>
                        <span class="fw-semibold">{{ number_format((float) ($stats['closed_sessions'] ?? 0), 0) }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">{{ __('Price Changes') }}</span>
                        <span class="fw-semibold">{{ number_format((float) ($stats['price_changes'] ?? 0), 0) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

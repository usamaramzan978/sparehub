@extends('layouts.system')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h4 class="mb-1">{{ __('System Dashboard') }}</h4>
            <p class="text-muted mb-0">{{ __('Control tenants, plans, and platform access from one place.') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('system.tenants.create') }}" class="btn btn-primary">{{ __('Create Tenant') }}</a>
            <a href="{{ route('system.plans.create') }}" class="btn btn-outline-primary">{{ __('Create Plan') }}</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted">{{ __('System Users') }}</div>
                    <h3 class="mb-0">{{ number_format($summary['system_users']) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted">{{ __('Tenants') }}</div>
                    <h3 class="mb-0">{{ number_format($summary['tenants_total']) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted">{{ __('Active Tenants') }}</div>
                    <h3 class="mb-0">{{ number_format($summary['tenants_active']) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted">{{ __('Plans') }}</div>
                    <h3 class="mb-0">{{ number_format($summary['plans_total']) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">{{ __('Recent Tenants') }}</h6>
                    <a href="{{ route('system.tenants.index') }}" class="btn btn-sm btn-outline-secondary">{{ __('View All') }}</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>{{ __('Tenant') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Plan') }}</th>
                                    <th>{{ __('Domain') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentTenants as $tenant)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $tenant->name }}</div>
                                            <small class="text-muted">{{ $tenant->slug }}</small>
                                        </td>
                                        <td><span class="badge bg-info-transparent">{{ strtoupper($tenant->status?->value ?? (string) $tenant->status) }}</span></td>
                                        <td>{{ $tenant->plan?->name ?? '-' }}</td>
                                        <td>{{ $tenant->domains->first()?->domain ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">{{ __('No tenants found.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">{{ __('Recent System Users') }}</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Email') }}</th>
                                    <th>{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentSystemUsers as $user)
                                    <tr>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td><span class="badge bg-success-transparent">{{ strtoupper($user->status?->value ?? (string) $user->status) }}</span></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">{{ __('No users found.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

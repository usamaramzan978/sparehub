@extends('layouts.system')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h4 class="mb-1">{{ __('Tenants') }}</h4>
            <p class="text-muted mb-0">{{ __('Create and manage all central tenants.') }}</p>
        </div>
        <a href="{{ route('system.tenants.create') }}" class="btn btn-primary">{{ __('Create Tenant') }}</a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('system.tenants.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-6 col-xl-4">
                    <label for="tenant-search" class="form-label">{{ __('Search') }}</label>
                    <input id="tenant-search" type="text" name="search" class="form-control" value="{{ $search }}"
                        placeholder="Name, slug, or domain">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                </div>
                <div class="col-auto">
                    <a href="{{ route('system.tenants.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('Tenant') }}</th>
                            <th>{{ __('Plan') }}</th>
                            <th>{{ __('Domain') }}</th>
                            <th>{{ __('Owner') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tenants as $tenant)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $tenant->name }}</div>
                                    <small class="text-muted">{{ $tenant->slug }}</small>
                                </td>
                                <td>{{ $tenant->plan?->name ?? '-' }}</td>
                                <td>{{ $tenant->domains->first()?->domain ?? '-' }}</td>
                                <td>
                                    <div>{{ $tenant->data['owner_name'] ?? '-' }}</div>
                                    <small class="text-muted">{{ $tenant->data['owner_email'] ?? '' }}</small>
                                </td>
                                <td><span
                                        class="badge bg-info-transparent">{{ strtoupper($tenant->status?->value ?? (string) $tenant->status) }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('system.tenants.edit', $tenant) }}"
                                        class="btn btn-sm btn-secondary-light">
                                        <i class="ri-edit-line"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">{{ __('No tenants found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">{{ $tenants->links() }}</div>
    </div>
@endsection

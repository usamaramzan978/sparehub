@extends('layouts.system')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h4 class="mb-1">{{ __('Tenant Users') }}</h4>
            <p class="text-muted mb-0">{{ __('Manage login users for each tenant.') }}</p>
        </div>
        <a href="{{ route('system.tenant-users.create', ['tenant_id' => request('tenant_id')]) }}" class="btn btn-primary">
            {{ __('Create Tenant User') }}
        </a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('system.tenant-users.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-6 col-xl-4">
                    <label for="tenant-id" class="form-label">{{ __('Tenant') }}</label>
                    <select id="tenant-id" name="tenant_id" class="form-select">
                        <option value="">{{ __('Select Tenant') }}</option>
                        @foreach ($tenants as $tenant)
                            <option value="{{ $tenant->id }}" @selected((string) request('tenant_id') === (string) $tenant->id)>
                                {{ $tenant->name }} ({{ $tenant->slug }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                </div>
                <div class="col-auto">
                    <a href="{{ route('system.tenant-users.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Email') }}</th>
                            <th>{{ __('Phone') }}</th>
                            <th>{{ __('Branch') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Created') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (! $selectedTenant)
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">{{ __('Select a tenant to view users.') }}</td>
                            </tr>
                        @else
                            @forelse ($tenantUsers as $user)
                                <tr>
                                    <td>{{ $user['name'] }}</td>
                                    <td>{{ $user['email'] }}</td>
                                    <td>{{ $user['phone'] ?: '-' }}</td>
                                    <td>{{ $user['branch'] ?: '-' }}</td>
                                    <td><span class="badge bg-info-transparent">{{ strtoupper($user['status']) }}</span></td>
                                    <td>{{ $user['created_at'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">{{ __('No users found for selected tenant.') }}</td>
                                </tr>
                            @endforelse
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

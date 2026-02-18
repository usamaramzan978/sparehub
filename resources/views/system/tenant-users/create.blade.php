@extends('layouts.system')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">{{ __('Create Tenant User') }}</h4>
        <a href="{{ route('system.tenant-users.index', ['tenant_id' => old('tenant_id', $selectedTenantId)]) }}"
            class="btn btn-outline-secondary">{{ __('Back') }}</a>
    </div>

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-body">
            <form method="POST" action="{{ route('system.tenant-users.store') }}">
                @csrf

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label for="tenant-id" class="form-label">{{ __('Tenant') }}</label>
                        <select id="tenant-id" name="tenant_id" class="form-select" required>
                            <option value="">{{ __('Select Tenant') }}</option>
                            @foreach ($tenants as $tenant)
                                <option value="{{ $tenant->id }}" @selected((string) old('tenant_id', $selectedTenantId) === (string) $tenant->id)>
                                    {{ $tenant->name }} ({{ $tenant->slug }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="user-status" class="form-label">{{ __('Status') }}</label>
                        <select id="user-status" name="status" class="form-select" required>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" @selected(old('status', 'active') === $status->value)>
                                    {{ ucfirst($status->value) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="user-name" class="form-label">{{ __('Name') }}</label>
                        <input id="user-name" type="text" name="name" class="form-control"
                            value="{{ old('name') }}" required>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="user-email" class="form-label">{{ __('Email') }}</label>
                        <input id="user-email" type="email" name="email" class="form-control"
                            value="{{ old('email') }}" required>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="user-phone" class="form-label">{{ __('Phone') }}</label>
                        <input id="user-phone" type="text" name="phone" class="form-control"
                            value="{{ old('phone') }}">
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="user-password" class="form-label">{{ __('Password') }}</label>
                        <input id="user-password" type="password" name="password" class="form-control" required>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="user-password-confirmation" class="form-label">{{ __('Confirm Password') }}</label>
                        <input id="user-password-confirmation" type="password" name="password_confirmation"
                            class="form-control" required>
                    </div>
                </div>

                <div class="mt-4 d-flex justify-content-end gap-2">
                    <a href="{{ route('system.tenant-users.index', ['tenant_id' => old('tenant_id', $selectedTenantId)]) }}"
                        class="btn btn-light">{{ __('Cancel') }}</a>
                    <button type="submit" class="btn btn-primary">{{ __('Create User') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection

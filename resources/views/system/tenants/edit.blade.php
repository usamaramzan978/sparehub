@extends('layouts.system')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">{{ __('Edit Tenant') }}</h4>
        <a href="{{ route('system.tenants.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
    </div>

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-body">
            <form method="POST" action="{{ route('system.tenants.update', $tenant) }}">
                @csrf
                @method('PUT')
                @include('system.tenants.partials.form', ['isCreate' => false])

                <div class="mt-4 d-flex justify-content-end gap-2">
                    <a href="{{ route('system.tenants.index') }}" class="btn btn-light">{{ __('Cancel') }}</a>
                    <button type="submit" class="btn btn-primary">{{ __('Update Tenant') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@extends('layouts.app')

@section('content')
    <div class="row g-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-2">{{ __('Dashboard') }}</h5>
                    <p class="mb-0 text-muted">{{ __('Tenant authentication is configured. You can now continue building modules.') }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection

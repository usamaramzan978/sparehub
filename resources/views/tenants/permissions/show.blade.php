@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Access Control')],
            ['label' => __('Permissions'), 'url' => route('tenant.permissions.index')],
            ['label' => __('Details')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Permission Details') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.permissions.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="text-muted">{{ __('Name') }}</div>
                    <div class="fw-semibold">{{ $permission->name }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted">{{ __('Guard') }}</div>
                    <div class="fw-semibold">{{ $permission->guard_name }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection

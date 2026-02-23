@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('People')],
            ['label' => __('Users'), 'url' => route('tenant.users.index')],
            ['label' => __('Details')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('User Details') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.users.edit', $user) }}" class="btn btn-secondary">{{ __('Edit') }}</a>
            <button type="button" class="btn btn-danger js-delete-modal"
                data-action="{{ route('tenant.users.destroy', $user) }}" data-name="{{ $user->name }}"
                data-title="{{ __('Delete User') }}" data-message="{{ __('Are you sure you want to delete this user?') }}"
                data-bs-toggle="modal" data-bs-target="#userDeleteModal">
                {{ __('Delete') }}
            </button>
            <a href="{{ route('tenant.users.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card h-100">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted small">{{ __('Name') }}</div>
                            <div class="fw-semibold">{{ $user->name }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">{{ __('Email') }}</div>
                            <div class="fw-semibold">{{ $user->email }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">{{ __('Phone') }}</div>
                            <div class="fw-semibold">{{ $user->phone ?: '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">{{ __('Branch') }}</div>
                            <div class="fw-semibold">{{ $user->branch?->name ?: '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">{{ __('Status') }}</div>
                            <div class="fw-semibold">{{ ucfirst($user->status->value) }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">{{ __('Created At') }}</div>
                            <div class="fw-semibold">@tenantDate($user->created_at, 'Y-m-d H:i', '')</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">{{ __('Last Login') }}</div>
                            <div class="fw-semibold">@tenantDate($user->last_login_at, 'Y-m-d H:i')</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">{{ __('Last Login IP') }}</div>
                            <div class="fw-semibold">{{ $user->last_login_ip ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-delete-modal id="userDeleteModal" />
@endsection

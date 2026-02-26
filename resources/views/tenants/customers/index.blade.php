@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [['label' => __('People')], ['label' => __('Customers')]];
    @endphp

    <x-breadcrumb title="{{ __('Customers') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.customers.create') }}" class="btn btn-primary">{{ __('Add Customer') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card custom-card border-0 shadow-sm h-100" data-ajax-table-search
        data-form-selector="#customers-search-form" data-input-selector="#customers-search"
        data-table-body-selector="#customers-table tbody" data-pagination-selector="[data-customers-pagination]"
        data-loading-selector="#customers-search-loading" data-search-param="search" data-debounce="350"
        data-min-loading-visible="220">
        <div class="card-header d-flex justify-content-between align-items-end flex-wrap gap-3">
            <div class="card-title mb-0">
                {{ __('Customers') }}
            </div>
            <form method="GET" action="{{ route('tenant.customers.index') }}" class="d-flex align-items-end gap-2 flex-wrap"
                id="customers-search-form">
                <div class="position-relative">
                    <input type="text" name="search" id="customers-search" class="form-control pe-5"
                        value="{{ request('search') }}" placeholder="{{ __('Search by code, name, phone, email') }}">
                    <span id="customers-search-loading"
                        class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted opacity-0 pe-none"
                        style="transition: opacity 0.2s ease;" aria-hidden="true">
                        <span class="spinner-border spinner-border-sm"></span>
                    </span>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0" id="customers-table">
                    <thead>
                        <tr>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Phone') }}</th>
                            <th>{{ __('Email') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $customer)
                            <tr>
                                <td>{{ $customer->code }}</td>
                                <td>{{ $customer->name }}</td>
                                <td>{{ $customer->phone ?: '-' }}</td>
                                <td>{{ $customer->email ?: '-' }}</td>
                                <td>
                                    @if ($customer->status->value === 'active')
                                        <span class="badge bg-success-transparent">{{ __('Active') }}</span>
                                    @elseif ($customer->status->value === 'inactive')
                                        <span class="badge bg-secondary-transparent">{{ __('Inactive') }}</span>
                                    @else
                                        <span class="badge bg-danger-transparent">{{ __('Blocked') }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-list">
                                        <a href="{{ route('tenant.customers.show', $customer) }}"
                                            class="btn btn-sm btn-icon btn-primary-light btn-wave waves-effect waves-light"
                                            title="{{ __('View') }}">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                        <a href="{{ route('tenant.customers.edit', $customer) }}"
                                            class="btn btn-sm btn-icon btn-secondary-light btn-wave waves-effect waves-light"
                                            title="{{ __('Edit') }}">
                                            <i class="ri-edit-line"></i>
                                        </a>
                                        <button type="button"
                                            class="btn btn-sm btn-icon btn-danger-light btn-wave waves-effect waves-light js-delete-modal"
                                            data-action="{{ route('tenant.customers.destroy', $customer) }}"
                                            data-name="{{ $customer->name }}" data-title="{{ __('Delete Customer') }}"
                                            data-message="{{ __('Are you sure you want to delete this customer?') }}"
                                            data-bs-toggle="modal" data-bs-target="#customerDeleteModal"
                                            title="{{ __('Delete') }}">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">{{ __('No customers found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3" data-customers-pagination>
                {{ $items->links() }}
            </div>
        </div>
    </div>

    <x-delete-modal id="customerDeleteModal" />
@endsection

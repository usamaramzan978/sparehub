@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [['label' => __('People')], ['label' => __('Vendors')]];
    @endphp

    <x-breadcrumb title="{{ __('Vendors') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.vendors.create') }}" class="btn btn-primary">{{ __('Add Vendor') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card custom-card border-0 shadow-sm h-100" data-ajax-table-search
        data-form-selector="#vendors-search-form" data-input-selector="#vendors-search"
        data-table-body-selector="#vendors-table tbody" data-pagination-selector="[data-vendors-pagination]"
        data-loading-selector="#vendors-search-loading" data-search-param="search" data-debounce="350"
        data-min-loading-visible="220">
        <div class="card-header d-flex justify-content-between align-items-end flex-wrap gap-3">
            <div class="card-title mb-0">
                {{ __('Vendors') }}
            </div>
            <form method="GET" action="{{ route('tenant.vendors.index') }}" class="d-flex align-items-end gap-2 flex-wrap"
                id="vendors-search-form">
                <div class="position-relative">
                    <input type="text" name="search" id="vendors-search" class="form-control pe-5"
                        value="{{ request('search') }}" placeholder="{{ __('Code, name, phone') }}">
                    <span id="vendors-search-loading"
                        class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted opacity-0 pe-none"
                        style="transition: opacity 0.2s ease;" aria-hidden="true">
                        <span class="spinner-border spinner-border-sm"></span>
                    </span>
                </div>
            </form>
        </div>
        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0" id="vendors-table">
                    <thead>
                        <tr>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Phone') }}</th>
                            <th>{{ __('City') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $vendor)
                            <tr>
                                <td>{{ $vendor->code }}</td>
                                <td>{{ $vendor->name }}</td>
                                <td>{{ $vendor->phone ?: '-' }}</td>
                                <td>{{ $vendor->city ?: '-' }}</td>
                                <td>
                                    @if ($vendor->status->value === 'active')
                                        <span class="badge bg-success-transparent">{{ __('Active') }}</span>
                                    @else
                                        <span class="badge bg-secondary-transparent">{{ __('Inactive') }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-list">
                                        <a href="{{ route('tenant.vendors.show', $vendor) }}"
                                            class="btn btn-sm btn-icon btn-primary-light btn-wave waves-effect waves-light"
                                            title="{{ __('View') }}">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                        <a href="{{ route('tenant.vendors.edit', $vendor) }}"
                                            class="btn btn-sm btn-icon btn-secondary-light btn-wave waves-effect waves-light"
                                            title="{{ __('Edit') }}">
                                            <i class="ri-edit-line"></i>
                                        </a>
                                        <button type="button"
                                            class="btn btn-sm btn-icon btn-danger-light btn-wave waves-effect waves-light js-delete-modal"
                                            data-action="{{ route('tenant.vendors.destroy', $vendor) }}"
                                            data-name="{{ $vendor->name }}" data-title="{{ __('Delete Vendor') }}"
                                            data-message="{{ __('Are you sure you want to delete this vendor?') }}"
                                            data-bs-toggle="modal" data-bs-target="#vendorDeleteModal"
                                            title="{{ __('Delete') }}">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">{{ __('No vendors found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3" data-vendors-pagination>
                {{ $items->links() }}
            </div>
        </div>
    </div>

    <x-delete-modal id="vendorDeleteModal" />
@endsection

@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [['label' => __('Catalog')], ['label' => __('Service Catalog')]];
    @endphp

    <x-breadcrumb title="{{ __('Service Catalog') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.service-catalog.create') }}" class="btn btn-primary">{{ __('Add Service') }}</a>
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
        data-form-selector="#service-catalog-search-form" data-input-selector="#service-catalog-search"
        data-table-body-selector="#service-catalog-table tbody"
        data-pagination-selector="[data-service-catalog-pagination]"
        data-loading-selector="#service-catalog-search-loading" data-search-param="search" data-debounce="350"
        data-min-loading-visible="220">
        <div class="card-header d-flex justify-content-between align-items-end flex-wrap gap-3">
            <div class="card-title mb-0">
                {{ __('Service Catalog') }}
            </div>
            <form method="GET" action="{{ route('tenant.service-catalog.index') }}"
                class="d-flex align-items-end gap-2 flex-wrap" id="service-catalog-search-form">
                <div class="position-relative">
                    <input type="text" name="search" id="service-catalog-search" class="form-control pe-5"
                        value="{{ request('search') }}" placeholder="{{ __('Search by code, name, category') }}">
                    <span id="service-catalog-search-loading"
                        class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted opacity-0 pe-none"
                        style="transition: opacity 0.2s ease;" aria-hidden="true">
                        <span class="spinner-border spinner-border-sm"></span>
                    </span>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0" id="service-catalog-table">
                    <thead>
                        <tr>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Category') }}</th>
                            <th>{{ __('Base Price') }}</th>
                            <th>{{ __('Duration (Min)') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $serviceCatalog)
                            <tr>
                                <td>{{ $serviceCatalog->code }}</td>
                                <td>{{ $serviceCatalog->name }}</td>
                                <td>{{ $serviceCatalog->category ?: '-' }}</td>
                                <td>{{ number_format((float) $serviceCatalog->base_price, 2) }}</td>
                                <td>{{ $serviceCatalog->duration_minutes ?: '-' }}</td>
                                <td>
                                    @if ($serviceCatalog->status->value === 'active')
                                        <span class="badge bg-success-transparent">{{ __('Active') }}</span>
                                    @else
                                        <span class="badge bg-secondary-transparent">{{ __('Inactive') }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-list">
                                        <span class="d-inline-block" data-bs-toggle="tooltip" title="{{ __('View') }}">
                                            <a href="{{ route('tenant.service-catalog.show', $serviceCatalog) }}"
                                                class="btn btn-sm btn-icon btn-primary-light btn-wave waves-effect waves-light">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                        </span>
                                        <span class="d-inline-block" data-bs-toggle="tooltip" title="{{ __('Edit') }}">
                                            <a href="{{ route('tenant.service-catalog.edit', $serviceCatalog) }}"
                                                class="btn btn-sm btn-icon btn-secondary-light btn-wave waves-effect waves-light"
                                                title="{{ __('Edit') }}">
                                                <i class="ri-edit-line"></i>
                                            </a>
                                        </span>
                                        <span class="d-inline-block" data-bs-toggle="tooltip" title="{{ __('Delete') }}">
                                            <button type="button"
                                                class="btn btn-sm btn-icon btn-danger-light btn-wave waves-effect waves-light js-delete-modal"
                                                data-action="{{ route('tenant.service-catalog.destroy', $serviceCatalog) }}"
                                                data-name="{{ $serviceCatalog->name }}"
                                                data-title="{{ __('Delete Service') }}"
                                                data-message="{{ __('Are you sure you want to delete this service?') }}"
                                                data-bs-toggle="modal" data-bs-target="#serviceCatalogDeleteModal">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">{{ __('No services found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3" data-service-catalog-pagination>
                {{ $items->links() }}
            </div>
        </div>
    </div>

    <x-delete-modal id="serviceCatalogDeleteModal" />
@endsection

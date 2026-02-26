@extends('layouts.app')

@section('content')
    @include('tenants.brands.partials.create', ['statuses' => $statuses])

    @php
        $breadcrumbs = [['label' => __('Catalog')], ['label' => __('Brands')]];
    @endphp

    <x-breadcrumb title="{{ __('Brands') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#brandCreateModal">
                {{ __('Add Brand') }}
            </button>
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

    <div class="card custom-card border-0 shadow-sm h-100" data-ajax-table-search data-form-selector="#brands-search-form"
        data-input-selector="#search" data-table-body-selector="#brands-table tbody"
        data-pagination-selector="[data-brands-pagination]" data-loading-selector="#brands-search-loading"
        data-search-param="search" data-debounce="350" data-min-loading-visible="220">
        <div class="card-header d-flex justify-content-between align-items-end flex-wrap gap-3">
            <div class="card-title mb-0">
                Brands
            </div>
            <form method="GET" action="{{ route('tenant.brands.index') }}" class="d-flex align-items-end gap-2 flex-wrap"
                id="brands-search-form">
                <div class="position-relative">
                    <input type="text" name="search" id="search" class="form-control pe-5"
                        value="{{ request('search') }}" placeholder="{{ __('Search by name') }}">
                    <span id="brands-search-loading"
                        class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted opacity-0 pe-none"
                        style="transition: opacity 0.2s ease;" aria-hidden="true">
                        <span class="spinner-border spinner-border-sm"></span>
                    </span>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0" id="brands-table">
                    <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Products') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $brand)
                            <tr>
                                <td>{{ $brand->name }}</td>
                                <td>
                                    <span class="badge bg-info-transparent">
                                        {{ (int) $brand->products_count }} {{ __('Products') }}
                                    </span>
                                </td>
                                <td>
                                    @if ($brand->status->value === 'active')
                                        <span class="badge bg-success-transparent">
                                            {{ __('Active') }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-transparent">
                                            {{ __('Inactive') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-list">
                                        <span class="d-inline-block" data-bs-toggle="tooltip" title="{{ __('View') }}">
                                            <button type="button"
                                                class="btn btn-sm btn-icon btn-primary-light btn-wave waves-effect waves-light js-view-brand"
                                                data-name="{{ $brand->name }}" data-status="{{ $brand->status->value }}"
                                                data-bs-toggle="modal" data-bs-target="#brandViewModal">
                                                <i class="ri-eye-line"></i>
                                            </button>
                                        </span>
                                        <span class="d-inline-block" data-bs-toggle="tooltip" title="{{ __('Edit') }}">
                                            <button type="button"
                                                class="btn btn-sm btn-icon btn-secondary-light btn-wave waves-effect waves-light"
                                                data-bs-toggle="modal"
                                                data-bs-target="#brandEditModal-{{ $brand->id }}">
                                                <i class="ri-edit-line"></i>
                                            </button>
                                        </span>
                                        <span class="d-inline-block" data-bs-toggle="tooltip" title="{{ __('Delete') }}">
                                            <button type="button"
                                                class="btn btn-sm btn-icon btn-danger-light btn-wave waves-effect waves-light js-delete-modal"
                                                data-action="{{ route('tenant.brands.destroy', $brand) }}"
                                                data-name="{{ $brand->name }}" data-title="{{ __('Delete Brand') }}"
                                                data-message="{{ __('Are you sure you want to delete this brand?') }}"
                                                data-bs-toggle="modal" data-bs-target="#brandDeleteModal">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            @include('tenants.brands.partials.edit', [
                                'brand' => $brand,
                                'statuses' => $statuses,
                            ])
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">{{ __('No brands found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3" data-brands-pagination>
                {{ $items->links() }}
            </div>
        </div>
    </div>

    <div class="modal fade" id="brandViewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Brand Details') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3">{{ __('Name') }}</dt>
                        <dd class="col-sm-9" id="brand-view-name">-</dd>
                        <dt class="col-sm-3">{{ __('Status') }}</dt>
                        <dd class="col-sm-9" id="brand-view-status">-</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <x-delete-modal id="brandDeleteModal" />

    @push('scripts')
        <script>
            document.addEventListener('click', (event) => {
                const target = event.target;

                if (!(target instanceof HTMLElement)) {
                    return;
                }

                const button = target.closest('.js-view-brand');

                if (!button) {
                    return;
                }

                document.getElementById('brand-view-name').textContent = button.dataset.name || '-';
                document.getElementById('brand-view-status').textContent = button.dataset.status || '-';
            });
        </script>
    @endpush
@endsection

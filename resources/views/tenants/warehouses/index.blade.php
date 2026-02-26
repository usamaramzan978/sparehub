@extends('layouts.app')

@section('content')
    @include('tenants.warehouses.partials.create', ['statuses' => $statuses])

    @php
        $breadcrumbs = [['label' => __('Master Data')], ['label' => __('Warehouses')]];
    @endphp

    <x-breadcrumb title="{{ __('Warehouses') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#warehouseCreateModal">
                {{ __('Add Warehouse') }}
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

    <div class="card custom-card border-0 shadow-sm h-100" data-ajax-table-search
        data-form-selector="#warehouses-search-form" data-input-selector="#warehouses-search"
        data-table-body-selector="#warehouses-table tbody" data-pagination-selector="[data-warehouses-pagination]"
        data-loading-selector="#warehouses-search-loading" data-search-param="search" data-debounce="350"
        data-min-loading-visible="220">
        <div class="card-header d-flex justify-content-between align-items-end flex-wrap gap-3">
            <div class="card-title mb-0">
                {{ __('Warehouses') }}
            </div>
            <form method="GET" action="{{ route('tenant.warehouses.index') }}"
                class="d-flex align-items-end gap-2 flex-wrap" id="warehouses-search-form">
                <div class="position-relative">
                    <input type="text" name="search" id="warehouses-search" class="form-control pe-5"
                        value="{{ request('search') }}" placeholder="{{ __('Search by name, code') }}">
                    <span id="warehouses-search-loading"
                        class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted opacity-0 pe-none"
                        style="transition: opacity 0.2s ease;" aria-hidden="true">
                        <span class="spinner-border spinner-border-sm"></span>
                    </span>
                </div>
            </form>
        </div>
        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0" id="warehouses-table">
                    <thead>
                        <tr>
                            <x-sortable-column :label="__('Code')" column="code" :current-sort-by="$sortBy"
                                :current-sort-direction="$sortDirection" />
                            <x-sortable-column :label="__('Name')" column="name" :current-sort-by="$sortBy"
                                :current-sort-direction="$sortDirection" />
                            <th>{{ __('Branch') }}</th>
                            <x-sortable-column :label="__('Status')" column="status" :current-sort-by="$sortBy"
                                :current-sort-direction="$sortDirection" />
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $warehouse)
                            <tr>
                                <td>{{ $warehouse->code }}</td>
                                <td>{{ $warehouse->name }}</td>
                                <td>{{ $warehouse->branch?->name ?: '-' }}</td>
                                <td>
                                    @if ($warehouse->status->value === 'active')
                                        <span class="badge bg-success-transparent">{{ __('Active') }}</span>
                                    @else
                                        <span class="badge bg-secondary-transparent">{{ __('Inactive') }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-list">
                                        <span class="d-inline-block" data-bs-toggle="tooltip" title="{{ __('View') }}">
                                            <button type="button"
                                                class="btn btn-sm btn-icon btn-primary-light btn-wave waves-effect waves-light js-view-warehouse"
                                                data-code="{{ $warehouse->code }}" data-name="{{ $warehouse->name }}"
                                                data-branch="{{ $warehouse->branch?->name ?: '-' }}"
                                                data-status="{{ $warehouse->status->value }}" data-bs-toggle="modal"
                                                data-bs-target="#warehouseViewModal">
                                                <i class="ri-eye-line"></i>
                                            </button>
                                        </span>
                                        <span class="d-inline-block" data-bs-toggle="tooltip" title="{{ __('Edit') }}">
                                            <button type="button"
                                                class="btn btn-sm btn-icon btn-secondary-light btn-wave waves-effect waves-light"
                                                data-bs-toggle="modal"
                                                data-bs-target="#warehouseEditModal-{{ $warehouse->id }}">
                                                <i class="ri-edit-line"></i>
                                            </button>
                                        </span>
                                        <span class="d-inline-block" data-bs-toggle="tooltip" title="{{ __('Delete') }}">
                                            <button type="button"
                                                class="btn btn-sm btn-icon btn-danger-light btn-wave waves-effect waves-light js-delete-modal"
                                                data-action="{{ route('tenant.warehouses.destroy', $warehouse) }}"
                                                data-name="{{ $warehouse->name }}"
                                                data-title="{{ __('Delete Warehouse') }}"
                                                data-message="{{ __('Are you sure you want to delete this warehouse?') }}"
                                                data-bs-toggle="modal" data-bs-target="#warehouseDeleteModal">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            @include('tenants.warehouses.partials.edit', [
                                'warehouse' => $warehouse,
                                'statuses' => $statuses,
                            ])
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">{{ __('No warehouses found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3" data-warehouses-pagination>
                {{ $items->links() }}
            </div>
        </div>
    </div>

    <div class="modal fade" id="warehouseViewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Warehouse Details') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3">{{ __('Code') }}</dt>
                        <dd class="col-sm-9" id="warehouse-view-code">-</dd>
                        <dt class="col-sm-3">{{ __('Name') }}</dt>
                        <dd class="col-sm-9" id="warehouse-view-name">-</dd>
                        <dt class="col-sm-3">{{ __('Branch') }}</dt>
                        <dd class="col-sm-9" id="warehouse-view-branch">-</dd>
                        <dt class="col-sm-3">{{ __('Status') }}</dt>
                        <dd class="col-sm-9" id="warehouse-view-status">-</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <x-delete-modal id="warehouseDeleteModal" />

    @push('scripts')
        <script>
            document.addEventListener('click', (event) => {
                const target = event.target;

                if (!(target instanceof HTMLElement)) {
                    return;
                }

                const button = target.closest('.js-view-warehouse');

                if (!button) {
                    return;
                }

                document.getElementById('warehouse-view-code').textContent = button.dataset.code || '-';
                document.getElementById('warehouse-view-name').textContent = button.dataset.name || '-';
                document.getElementById('warehouse-view-branch').textContent = button.dataset.branch || '-';
                document.getElementById('warehouse-view-status').textContent = button.dataset.status || '-';
            });
        </script>
    @endpush
@endsection

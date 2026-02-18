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

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-body">
            <form method="GET" action="{{ route('tenant.warehouses.index') }}" class="row g-2 mb-3">
                <div class="col-md-10">
                    <label class="form-label" for="search">{{ __('Search') }}</label>
                    <input type="text" name="search" id="search" class="form-control" value="{{ request('search') }}"
                        placeholder="{{ __('Search by warehouse name or code') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100">{{ __('Filter') }}</button>
                    <a href="{{ route('tenant.warehouses.index') }}"
                        class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Branch') }}</th>
                            <th>{{ __('Status') }}</th>
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

            <div class="mt-3">
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
            document.querySelectorAll('.js-view-warehouse').forEach((button) => {
                button.addEventListener('click', () => {
                    document.getElementById('warehouse-view-code').textContent = button.dataset.code || '-';
                    document.getElementById('warehouse-view-name').textContent = button.dataset.name || '-';
                    document.getElementById('warehouse-view-branch').textContent = button.dataset.branch || '-';
                    document.getElementById('warehouse-view-status').textContent = button.dataset.status || '-';
                });
            });
        </script>
    @endpush
@endsection

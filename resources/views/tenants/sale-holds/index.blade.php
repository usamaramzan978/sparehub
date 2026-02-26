@extends('layouts.app')

@section('content')
    @include('tenants.sale-holds.partials.create', ['customers' => $customers])

    @php
        $breadcrumbs = [['label' => __('Sales')], ['label' => __('Sale Holds (POS Hold)')]];
    @endphp

    <x-breadcrumb title="{{ __('Sale Holds (POS Hold)') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#saleHoldCreateModal">
                {{ __('Add Hold') }}
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

    <div class="card custom-card border-0 shadow-sm h-100"
        @include('components.ajax-table-attributes', ['formSelector' => '#sale-holds-search-form', 'inputSelector' => '#sale-holds-search', 'tableBodySelector' => '#sale-holds-table tbody', 'paginationSelector' => '[data-sale-holds-pagination]', 'loadingSelector' => '#sale-holds-search-loading', 'searchParam' => 'search', 'debounce' => '350', 'minLoadingVisible' => '220'])>
        <div class="card-header d-flex justify-content-between align-items-end flex-wrap gap-3">
            <div class="card-title mb-0">
                {{ __('Sale Holds (POS Hold)') }}
            </div>
            <form method="GET" action="{{ route('tenant.sale-holds.index') }}"
                class="d-flex align-items-end gap-2 flex-wrap" id="sale-holds-search-form">
                <div class="position-relative">
                    <input type="text" class="form-control pe-5" id="sale-holds-search" name="search"
                        value="{{ request('search') }}" placeholder="{{ __('Search by hold no or customer') }}">
                    <span id="sale-holds-search-loading"
                        class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted opacity-0 pe-none"
                        style="transition: opacity 0.2s ease;" aria-hidden="true">
                        <span class="spinner-border spinner-border-sm"></span>
                    </span>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0" id="sale-holds-table">
                    <thead>
                        <tr>
                            <x-sortable-column :label="__('Hold No')" column="hold_no" :current-sort-by="$sortBy"
                                :current-sort-direction="$sortDirection" />
                            <th>{{ __('Customer') }}</th>
                            <x-sortable-column :label="__('Expires At')" column="expires_at" :current-sort-by="$sortBy"
                                :current-sort-direction="$sortDirection" />
                            <x-sortable-column :label="__('Created')" column="created_at" :current-sort-by="$sortBy"
                                :current-sort-direction="$sortDirection" />
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $hold)
                            <tr>
                                <td>{{ $hold->hold_no }}</td>
                                <td>{{ $hold->customer?->name ?? '-' }}</td>
                                <td>@tenantDate($hold->expires_at, 'Y-m-d H:i')</td>
                                <td>@tenantDate($hold->created_at, 'Y-m-d H:i', '')</td>
                                <td class="text-end">
                                    <div class="btn-list">
                                        <a href="{{ route('tenant.sale-holds.show', $hold) }}"
                                            class="btn btn-sm btn-icon btn-primary-light btn-wave waves-effect waves-light"
                                            title="{{ __('View') }}">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                        <button type="button"
                                            class="btn btn-sm btn-icon btn-secondary-light btn-wave waves-effect waves-light"
                                            data-bs-toggle="modal" data-bs-target="#saleHoldEditModal-{{ $hold->id }}">
                                            <i class="ri-edit-line"></i>
                                        </button>
                                        <button type="button"
                                            class="btn btn-sm btn-icon btn-danger-light btn-wave waves-effect waves-light js-delete-modal"
                                            data-action="{{ route('tenant.sale-holds.destroy', $hold) }}"
                                            data-name="{{ $hold->hold_no }}" data-title="{{ __('Delete Sale Hold') }}"
                                            data-message="{{ __('Are you sure you want to delete this hold?') }}"
                                            data-bs-toggle="modal" data-bs-target="#saleHoldDeleteModal">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @include('tenants.sale-holds.partials.edit', [
                                'saleHold' => $hold,
                                'customers' => $customers,
                            ])
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">{{ __('No sale holds found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3" data-sale-holds-pagination>
                {{ $items->links() }}
            </div>
        </div>
    </div>

    <x-delete-modal id="saleHoldDeleteModal" />
@endsection

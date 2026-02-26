@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [['label' => __('Purchases')], ['label' => __('Purchase Orders')]];
    @endphp

    <x-breadcrumb title="{{ __('Purchase Orders') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.purchases.create') }}" class="btn btn-primary">{{ __('Add Purchase') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card custom-card border-0 shadow-sm h-100"
        @include('components.ajax-table-attributes', ['formSelector' => '#purchases-search-form', 'inputSelector' => '#purchases-search', 'tableBodySelector' => '#purchases-table tbody', 'paginationSelector' => '[data-purchases-pagination]', 'loadingSelector' => '#purchases-search-loading', 'searchParam' => 'search', 'debounce' => '350', 'minLoadingVisible' => '220'])>
        <div class="card-header d-flex justify-content-between align-items-end flex-wrap gap-3">
            <div class="card-title mb-0">
                {{ __('Purchase Orders') }}
            </div>
            <form method="GET" action="{{ route('tenant.purchases.index') }}"
                class="d-flex align-items-end gap-2 flex-wrap" id="purchases-search-form">
                <div class="position-relative">
                    <input type="text" class="form-control pe-5" id="purchases-search" name="search"
                        value="{{ request('search') }}"
                        placeholder="{{ __('Search by purchase no, vendor invoice, or vendor') }}">
                    <span id="purchases-search-loading"
                        class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted opacity-0 pe-none"
                        style="transition: opacity 0.2s ease;" aria-hidden="true">
                        <span class="spinner-border spinner-border-sm"></span>
                    </span>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0" id="purchases-table">
                    <thead>
                        <tr>
                            <x-sortable-column :label="__('Purchase No')" column="purchase_no" :current-sort-by="$sortBy"
                                :current-sort-direction="$sortDirection" />
                            <x-sortable-column :label="__('Date')" column="purchase_date" :current-sort-by="$sortBy"
                                :current-sort-direction="$sortDirection" />
                            <th>{{ __('Vendor') }}</th>
                            <th>{{ __('Warehouse') }}</th>
                            <x-sortable-column :label="__('Status')" column="status" :current-sort-by="$sortBy"
                                :current-sort-direction="$sortDirection" />
                            <x-sortable-column :label="__('Grand Total')" column="grand_total" :current-sort-by="$sortBy"
                                :current-sort-direction="$sortDirection" />
                            <x-sortable-column :label="__('Balance')" column="balance_due" :current-sort-by="$sortBy"
                                :current-sort-direction="$sortDirection" />
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $purchase)
                            <tr>
                                <td>{{ $purchase->purchase_no }}</td>
                                <td>@tenantDate($purchase->purchase_date, 'Y-m-d', '')</td>
                                <td>{{ $purchase->vendor?->name ?? '-' }}</td>
                                <td>{{ $purchase->warehouse?->name ?? '-' }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $purchase->status->value)) }}</td>
                                <td>{{ number_format((float) $purchase->grand_total, 2) }}</td>
                                <td>{{ number_format((float) $purchase->balance_due, 2) }}</td>
                                <td class="text-end">
                                    <div class="btn-list">
                                        <a href="{{ route('tenant.purchases.show', $purchase) }}"
                                            class="btn btn-sm btn-icon btn-primary-light btn-wave waves-effect waves-light"
                                            title="{{ __('View') }}">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                        <a href="{{ route('tenant.purchases.edit', $purchase) }}"
                                            class="btn btn-sm btn-icon btn-secondary-light btn-wave waves-effect waves-light">
                                            <i class="ri-edit-line"></i>
                                        </a>
                                        <button type="button"
                                            class="btn btn-sm btn-icon btn-danger-light btn-wave waves-effect waves-light js-delete-modal"
                                            data-action="{{ route('tenant.purchases.destroy', $purchase) }}"
                                            data-name="{{ $purchase->purchase_no }}"
                                            data-title="{{ __('Delete Purchase') }}"
                                            data-message="{{ __('Are you sure you want to delete this purchase?') }}"
                                            data-bs-toggle="modal" data-bs-target="#purchaseDeleteModal">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">{{ __('No purchases found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3" data-purchases-pagination>{{ $items->links() }}</div>
        </div>
    </div>

    <x-delete-modal id="purchaseDeleteModal" />
@endsection

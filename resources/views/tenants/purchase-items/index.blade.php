@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [['label' => __('Purchases')], ['label' => __('Purchase Items')]];
    @endphp

    <x-breadcrumb title="{{ __('Purchase Items') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.purchase-items.create') }}" class="btn btn-primary">{{ __('Add Purchase Item') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card custom-card border-0 shadow-sm h-100"
        @include('components.ajax-table-attributes', ['formSelector' => '#purchase-items-search-form', 'inputSelector' => '#purchase-items-search', 'tableBodySelector' => '#purchase-items-table tbody', 'paginationSelector' => '[data-purchase-items-pagination]', 'loadingSelector' => '#purchase-items-search-loading', 'searchParam' => 'search', 'debounce' => '350', 'minLoadingVisible' => '220'])>
        <div class="card-header d-flex justify-content-between align-items-end flex-wrap gap-3">
            <div class="card-title mb-0">
                {{ __('Purchase Items') }}
            </div>
            <form method="GET" action="{{ route('tenant.purchase-items.index') }}"
                class="d-flex align-items-end gap-2 flex-wrap" id="purchase-items-search-form">
                <div class="position-relative">
                    <input type="text" name="search" id="purchase-items-search" class="form-control pe-5"
                        value="{{ request('search') }}" placeholder="{{ __('Search by purchase no or product') }}">
                    <span id="purchase-items-search-loading"
                        class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted opacity-0 pe-none"
                        style="transition: opacity 0.2s ease;" aria-hidden="true">
                        <span class="spinner-border spinner-border-sm"></span>
                    </span>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0" id="purchase-items-table">
                    <thead>
                        <tr>
                            <th>{{ __('Purchase') }}</th>
                            <th>{{ __('Product') }}</th>
                            <x-sortable-column :label="__('Qty')" column="qty" :current-sort-by="$sortBy"
                                :current-sort-direction="$sortDirection" />
                            <x-sortable-column :label="__('Received')" column="received_qty" :current-sort-by="$sortBy"
                                :current-sort-direction="$sortDirection" />
                            <x-sortable-column :label="__('Unit Cost')" column="unit_cost" :current-sort-by="$sortBy"
                                :current-sort-direction="$sortDirection" />
                            <x-sortable-column :label="__('Line Total')" column="line_total" :current-sort-by="$sortBy"
                                :current-sort-direction="$sortDirection" />
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td>{{ $item->purchase?->purchase_no ?? '-' }}</td>
                                <td>{{ $item->product?->name ?? '-' }}</td>
                                <td>{{ number_format((float) $item->qty, 3) }}</td>
                                <td>{{ number_format((float) $item->received_qty, 3) }}</td>
                                <td>{{ number_format((float) $item->unit_cost, 2) }}</td>
                                <td>{{ number_format((float) $item->line_total, 2) }}</td>
                                <td class="text-end">
                                    <div class="btn-list">
                                        <a href="{{ route('tenant.purchase-items.show', $item) }}"
                                            class="btn btn-sm btn-icon btn-primary-light btn-wave waves-effect waves-light"
                                            title="{{ __('View') }}">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                        <a href="{{ route('tenant.purchase-items.edit', $item) }}"
                                            class="btn btn-sm btn-icon btn-secondary-light btn-wave waves-effect waves-light">
                                            <i class="ri-edit-line"></i>
                                        </a>
                                        <button type="button"
                                            class="btn btn-sm btn-icon btn-danger-light btn-wave waves-effect waves-light js-delete-modal"
                                            data-action="{{ route('tenant.purchase-items.destroy', $item) }}"
                                            data-name="{{ $item->id }}" data-title="{{ __('Delete Purchase Item') }}"
                                            data-message="{{ __('Are you sure you want to delete this purchase item?') }}"
                                            data-bs-toggle="modal" data-bs-target="#purchaseItemDeleteModal">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">{{ __('No purchase items found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3" data-purchase-items-pagination>{{ $items->links() }}</div>
        </div>
    </div>

    <x-delete-modal id="purchaseItemDeleteModal" />
@endsection

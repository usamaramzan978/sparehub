@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [['label' => __('Purchases')], ['label' => __('Purchase Return Items')]];
    @endphp

    <x-breadcrumb title="{{ __('Purchase Return Items') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.purchase-return-items.create') }}"
                class="btn btn-primary">{{ __('Add Return Item') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card custom-card border-0 shadow-sm h-100" data-ajax-table-search
        data-form-selector="#purchase-return-items-search-form" data-input-selector="#purchase-return-items-search"
        data-table-body-selector="#purchase-return-items-table tbody"
        data-pagination-selector="[data-purchase-return-items-pagination]"
        data-loading-selector="#purchase-return-items-search-loading" data-search-param="search" data-debounce="350"
        data-min-loading-visible="220">
        <div class="card-header d-flex justify-content-between align-items-end flex-wrap gap-3">
            <div class="card-title mb-0">
                {{ __('Purchase Return Items') }}
            </div>
            <form method="GET" action="{{ route('tenant.purchase-return-items.index') }}"
                class="d-flex align-items-end gap-2 flex-wrap" id="purchase-return-items-search-form">
                <div class="position-relative">
                    <input type="text" name="search" id="purchase-return-items-search" class="form-control pe-5"
                        value="{{ request('search') }}" placeholder="{{ __('Search by return no or product') }}">
                    <span id="purchase-return-items-search-loading"
                        class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted opacity-0 pe-none"
                        style="transition: opacity 0.2s ease;" aria-hidden="true">
                        <span class="spinner-border spinner-border-sm"></span>
                    </span>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0" id="purchase-return-items-table">
                    <thead>
                        <tr>
                            <th>{{ __('Return') }}</th>
                            <th>{{ __('Product') }}</th>
                            <x-sortable-column :label="__('Qty')" column="qty" :current-sort-by="$sortBy"
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
                                <td>{{ $item->purchaseReturn?->return_no ?? '-' }}</td>
                                <td>{{ $item->product?->name ?? '-' }}</td>
                                <td>{{ number_format((float) $item->qty, 3) }}</td>
                                <td>{{ number_format((float) $item->unit_cost, 2) }}</td>
                                <td>{{ number_format((float) $item->line_total, 2) }}</td>
                                <td class="text-end">
                                    <div class="btn-list">
                                        <a href="{{ route('tenant.purchase-return-items.show', $item) }}"
                                            class="btn btn-sm btn-icon btn-primary-light btn-wave waves-effect waves-light"
                                            title="{{ __('View') }}">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                        <a href="{{ route('tenant.purchase-return-items.edit', $item) }}"
                                            class="btn btn-sm btn-icon btn-secondary-light btn-wave waves-effect waves-light">
                                            <i class="ri-edit-line"></i>
                                        </a>
                                        <button type="button"
                                            class="btn btn-sm btn-icon btn-danger-light btn-wave waves-effect waves-light js-delete-modal"
                                            data-action="{{ route('tenant.purchase-return-items.destroy', $item) }}"
                                            data-name="{{ $item->id }}" data-title="{{ __('Delete Return Item') }}"
                                            data-message="{{ __('Are you sure you want to delete this return item?') }}"
                                            data-bs-toggle="modal" data-bs-target="#purchaseReturnItemDeleteModal">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">{{ __('No return items found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3" data-purchase-return-items-pagination>{{ $items->links() }}</div>
        </div>
    </div>

    <x-delete-modal id="purchaseReturnItemDeleteModal" />
@endsection

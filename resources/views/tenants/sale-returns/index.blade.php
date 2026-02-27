@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [['label' => __('Sales')], ['label' => __('Sales Returns')]];
    @endphp

    <x-breadcrumb title="{{ __('Sales Returns') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.sale-returns.create') }}" class="btn btn-primary">{{ __('Add Return') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card custom-card border-0 shadow-sm h-100"
        @include('components.ajax-table-attributes', ['formSelector' => '#sale-returns-search-form', 'inputSelector' => '#sale-returns-search', 'tableBodySelector' => '#sale-returns-table tbody', 'paginationSelector' => '[data-sale-returns-pagination]', 'loadingSelector' => '#sale-returns-search-loading', 'searchParam' => 'search', 'debounce' => '350', 'minLoadingVisible' => '220'])>
        <div class="card-header d-flex justify-content-between align-items-end flex-wrap gap-3">
            <div class="card-title mb-0">
                {{ __('Sales Returns') }}
            </div>
            <form method="GET" action="{{ route('tenant.sale-returns.index') }}"
                class="d-flex align-items-end gap-2 flex-wrap" id="sale-returns-search-form">
                <div class="position-relative">
                    <input type="text" class="form-control pe-5" id="sale-returns-search" name="search"
                        value="{{ request('search') }}" placeholder="{{ __('Search by return no or customer') }}">
                    <span id="sale-returns-search-loading"
                        class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted opacity-0 pe-none"
                        style="transition: opacity 0.2s ease;" aria-hidden="true">
                        <span class="spinner-border spinner-border-sm"></span>
                    </span>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0" id="sale-returns-table">
                    <thead>
                        <tr>
                            <x-sortable-column :label="__('Return No')" column="return_no" :current-sort-by="$sortBy"
                                :current-sort-direction="$sortDirection" />
                            <x-sortable-column :label="__('Date')" column="return_date" :current-sort-by="$sortBy"
                                :current-sort-direction="$sortDirection" />
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Invoice') }}</th>
                            <x-sortable-column :label="__('Status')" column="status" :current-sort-by="$sortBy"
                                :current-sort-direction="$sortDirection" />
                            <x-sortable-column :label="__('Grand Total')" column="grand_total" :current-sort-by="$sortBy"
                                :current-sort-direction="$sortDirection" />
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $return)
                            <tr>
                                <td>{{ $return->return_no }}</td>
                                <td>@tenantDate($return->return_date, 'Y-m-d', '')</td>
                                <td>{{ $return->customer?->name ?? '-' }}</td>
                                <td>{{ $return->sale?->invoice_no ?? '-' }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $return->status->value)) }}</td>
                                <td>{{ number_format((float) $return->grand_total, 2) }}</td>
                                <td class="text-end">
                                    <div class="btn-list">
                                        <a href="{{ route('tenant.sale-returns.show', $return) }}"
                                            class="btn btn-sm btn-icon btn-primary-light btn-wave waves-effect waves-light"
                                            title="{{ __('View') }}">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                        <a href="{{ route('tenant.sale-returns.edit', $return) }}"
                                            class="btn btn-sm btn-icon btn-secondary-light btn-wave waves-effect waves-light">
                                            <i class="ri-edit-line"></i>
                                        </a>
                                        <button type="button"
                                            class="btn btn-sm btn-icon btn-danger-light btn-wave waves-effect waves-light js-delete-modal"
                                            data-action="{{ route('tenant.sale-returns.destroy', $return) }}"
                                            data-name="{{ $return->return_no }}"
                                            data-title="{{ __('Delete Sales Return') }}"
                                            data-message="{{ __('Are you sure you want to delete this return?') }}"
                                            data-bs-toggle="modal" data-bs-target="#saleReturnDeleteModal">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">{{ __('No sales returns found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3" data-sale-returns-pagination>{{ $items->links() }}</div>
        </div>
    </div>

    <x-delete-modal id="saleReturnDeleteModal" />
@endsection

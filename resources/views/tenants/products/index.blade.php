@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [['label' => __('Catalog')], ['label' => __('Products')]];
    @endphp

    <x-breadcrumb title="{{ __('Products') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.products.create') }}" class="btn btn-primary">{{ __('Add Product') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card custom-card border-0 shadow-sm h-100"
        @include('components.ajax-table-attributes', ['formSelector' => '#products-search-form', 'inputSelector' => '#products-search', 'tableBodySelector' => '#products-table tbody', 'paginationSelector' => '[data-products-pagination]', 'loadingSelector' => '#products-search-loading', 'searchParam' => 'search', 'debounce' => '350', 'minLoadingVisible' => '220'])>
        <div class="card-header d-flex justify-content-between align-items-end flex-wrap gap-3">
            <div class="card-title mb-0">
                {{ __('Products') }}
            </div>
            <form method="GET" action="{{ route('tenant.products.index') }}" class="d-flex align-items-end gap-2 flex-wrap"
                id="products-search-form">
                <div class="position-relative">
                    <input type="text" name="search" id="products-search" class="form-control pe-5"
                        value="{{ request('search') }}" placeholder="{{ __('Name, SKU, part number') }}">
                    <span id="products-search-loading"
                        class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted opacity-0 pe-none"
                        style="transition: opacity 0.2s ease;" aria-hidden="true">
                        <span class="spinner-border spinner-border-sm"></span>
                    </span>
                </div>
            </form>
        </div>
        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0" id="products-table">
                    <thead>
                        <tr>
                            <x-sortable-column :label="__('Name')" column="name" :current-sort-by="$sortBy"
                                :current-sort-direction="$sortDirection" />
                            <x-sortable-column :label="__('SKU')" column="sku" :current-sort-by="$sortBy"
                                :current-sort-direction="$sortDirection" />
                            <th>{{ __('Category') }}</th>
                            <th>{{ __('Brand') }}</th>
                            <th class="text-end">{{ __('Stock') }}</th>
                            <x-sortable-column :label="__('Status')" column="status" :current-sort-by="$sortBy"
                                :current-sort-direction="$sortDirection" />
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $product)
                            <tr>
                                <td>{{ $product->name }}</td>
                                <td>{{ $product->sku }}</td>
                                <td>{{ $product->category?->name ?? '-' }}</td>
                                <td>{{ $product->brand?->name ?? '-' }}</td>
                                <td class="text-end">
                                    {{ number_format((float) $product->qty_on_hand, 0) }} /
                                    {{ number_format((float) $product->opening_stock, 0) }}
                                </td>
                                <td>
                                    @if ($product->status->value === 'active')
                                        <span class="badge bg-success-transparent">{{ __('Active') }}</span>
                                    @else
                                        <span class="badge bg-secondary-transparent">{{ __('Inactive') }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-list">
                                        <span class="d-inline-block" data-bs-toggle="tooltip" title="{{ __('View') }}">
                                            <a href="{{ route('tenant.products.show', $product) }}"
                                                class="btn btn-sm btn-icon btn-primary-light btn-wave waves-effect waves-light">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                        </span>
                                        <span class="d-inline-block" data-bs-toggle="tooltip" title="{{ __('Edit') }}">
                                            <a href="{{ route('tenant.products.edit', $product) }}"
                                                class="btn btn-sm btn-icon btn-secondary-light btn-wave waves-effect waves-light">
                                                <i class="ri-edit-line"></i>
                                            </a>
                                        </span>
                                        <span class="d-inline-block" data-bs-toggle="tooltip" title="{{ __('Delete') }}">
                                            <button type="button"
                                                class="btn btn-sm btn-icon btn-danger-light btn-wave waves-effect waves-light js-delete-modal"
                                                data-action="{{ route('tenant.products.destroy', $product) }}"
                                                data-name="{{ $product->name }}" data-title="{{ __('Delete Product') }}"
                                                data-message="{{ __('Are you sure you want to delete this product?') }}"
                                                data-bs-toggle="modal" data-bs-target="#productDeleteModal">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">{{ __('No products found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3" data-products-pagination>
                {{ $items->links() }}
            </div>
        </div>
    </div>

    <x-delete-modal id="productDeleteModal" />
@endsection

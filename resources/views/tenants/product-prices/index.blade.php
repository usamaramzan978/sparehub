@extends('layouts.app')

@section('content')
    @include('tenants.product-prices.partials.create', ['products' => $products])

    @php
        $breadcrumbs = [['label' => __('Catalog')], ['label' => __('Product Prices')]];
    @endphp

    <x-breadcrumb title="{{ __('Product Prices') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productPriceCreateModal">
                {{ __('Add Product Price') }}
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
        data-form-selector="#product-prices-search-form" data-input-selector="#product-prices-search"
        data-table-body-selector="#product-prices-table tbody"
        data-pagination-selector="[data-product-prices-pagination]" data-loading-selector="#product-prices-search-loading"
        data-search-param="search" data-debounce="350" data-min-loading-visible="220">
        <div class="card-header d-flex justify-content-between align-items-end flex-wrap gap-3">
            <div class="card-title mb-0">
                {{ __('Product Prices') }}
            </div>
            <form method="GET" action="{{ route('tenant.product-prices.index') }}"
                class="d-flex align-items-end gap-2 flex-wrap" id="product-prices-search-form">
                <div class="position-relative">
                    <input type="text" name="search" id="product-prices-search" class="form-control pe-5"
                        value="{{ request('search') }}" placeholder="{{ __('Search by product name or SKU') }}">
                    <span id="product-prices-search-loading"
                        class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted opacity-0 pe-none"
                        style="transition: opacity 0.2s ease;" aria-hidden="true">
                        <span class="spinner-border spinner-border-sm"></span>
                    </span>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0" id="product-prices-table">
                    <thead>
                        <tr>
                            <th>{{ __('Product') }}</th>
                            <th>{{ __('Cost') }}</th>
                            <th>{{ __('MRP') }}</th>
                            <th>{{ __('Retail') }}</th>
                            <th>{{ __('Wholesale') }}</th>
                            <th>{{ __('Effective From') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $productPrice)
                            <tr>
                                <td>{{ $productPrice->product?->name ?? '-' }}</td>
                                <td>{{ number_format((float) $productPrice->cost, 2) }}</td>
                                <td>{{ number_format((float) $productPrice->mrp, 2) }}</td>
                                <td>{{ number_format((float) $productPrice->retail_price, 2) }}</td>
                                <td>{{ number_format((float) $productPrice->wholesale_price, 2) }}</td>
                                <td>@tenantDate($productPrice->effective_from, 'Y-m-d H:i', '')</td>
                                <td class="text-end">
                                    <div class="btn-list">
                                        <span class="d-inline-block" data-bs-toggle="tooltip" title="{{ __('View') }}">
                                            <a href="{{ route('tenant.product-prices.show', $productPrice) }}"
                                                class="btn btn-sm btn-icon btn-primary-light btn-wave waves-effect waves-light">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                        </span>
                                        <span class="d-inline-block" data-bs-toggle="tooltip" title="{{ __('Edit') }}">
                                            <button type="button"
                                                class="btn btn-sm btn-icon btn-secondary-light btn-wave waves-effect waves-light"
                                                data-bs-toggle="modal"
                                                data-bs-target="#productPriceEditModal-{{ $productPrice->id }}">
                                                <i class="ri-edit-line"></i>
                                            </button>
                                        </span>
                                        <span class="d-inline-block" data-bs-toggle="tooltip" title="{{ __('Delete') }}">
                                            <button type="button"
                                                class="btn btn-sm btn-icon btn-danger-light btn-wave waves-effect waves-light js-delete-modal"
                                                data-action="{{ route('tenant.product-prices.destroy', $productPrice) }}"
                                                data-name="{{ $productPrice->product?->name ?? '-' }}"
                                                data-title="{{ __('Delete Product Price') }}"
                                                data-message="{{ __('Are you sure you want to delete this price entry?') }}"
                                                data-bs-toggle="modal" data-bs-target="#productPriceDeleteModal">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            @include('tenants.product-prices.partials.edit', [
                                'productPrice' => $productPrice,
                                'products' => $products,
                            ])
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">{{ __('No product prices found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3" data-product-prices-pagination>
                {{ $items->links() }}
            </div>
        </div>
    </div>

    <x-delete-modal id="productPriceDeleteModal" />
@endsection

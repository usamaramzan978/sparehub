@extends('layouts.app')

@section('content')
    @include('tenants.product-prices.partials.create', ['products' => $products])

    @php
        $breadcrumbs = [
            ['label' => __('Catalog')],
            ['label' => __('Product Prices')],
        ];
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

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
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
                                <td>{{ $productPrice->effective_from?->format('Y-m-d H:i') }}</td>
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

            <div class="mt-3">
                {{ $items->links() }}
            </div>
        </div>
    </div>

    <x-delete-modal id="productPriceDeleteModal" />
@endsection

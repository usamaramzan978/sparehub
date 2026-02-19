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

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-body">
            <form method="GET" action="{{ route('tenant.products.index') }}" class="row g-2 mb-3">
                <div class="col-md-10">
                    <label class="form-label" for="search">{{ __('Search') }}</label>
                    <input type="text" name="search" id="search" class="form-control" value="{{ request('search') }}"
                        placeholder="{{ __('Name, SKU, part number') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100">{{ __('Filter') }}</button>
                    <a href="{{ route('tenant.products.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('SKU') }}</th>
                            <th>{{ __('Category') }}</th>
                            <th>{{ __('Brand') }}</th>
                            <th class="text-end">{{ __('Stock') }}</th>
                            <th>{{ __('Status') }}</th>
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

            <div class="mt-3">
                {{ $items->links() }}
            </div>
        </div>
    </div>

    <x-delete-modal id="productDeleteModal" />
@endsection

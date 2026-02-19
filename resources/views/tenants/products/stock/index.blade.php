@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Catalog')],
            ['label' => __('Products')],
            ['label' => __('Stock')],
        ];
        $formatNumber = fn(float $value, int $decimals = 2): string => number_format($value, $decimals);
    @endphp

    <x-breadcrumb title="{{ __('Product Stock') }}" :items="$breadcrumbs" />

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('Product Categories') }}</div>
                    <div class="fs-4 fw-semibold">{{ $summary['categories_count'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('Tracked Products') }}</div>
                    <div class="fs-4 fw-semibold">{{ $summary['products_count'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('Current Stock (On Hand)') }}</div>
                    <div class="fs-4 fw-semibold">{{ $formatNumber($summary['qty_on_hand_total'], 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('Total Reserved') }}</div>
                    <div class="fs-4 fw-semibold">{{ $formatNumber($summary['qty_reserved_total'], 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('tenant.products.stock.index') }}" class="row g-2">
                <div class="col-md-9">
                    <label class="form-label" for="search">{{ __('Search') }}</label>
                    <input type="text" class="form-control" id="search" name="search" value="{{ $search }}"
                        placeholder="{{ __('Product name, SKU, part number, barcode') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label d-block">{{ __('Options') }}</label>
                    <div class="d-flex gap-2">
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" value="1" id="show_inactive"
                                name="show_inactive" {{ $showInactive ? 'checked' : '' }}>
                            <label class="form-check-label" for="show_inactive">{{ __('Show Inactive') }}</label>
                        </div>
                    </div>
                </div>
                <div class="col-12 d-flex gap-2 justify-content-end">
                    <button class="btn btn-primary" type="submit">{{ __('Filter') }}</button>
                    <a href="{{ route('tenant.products.stock.index') }}"
                        class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h6 class="card-title mb-0">{{ __('Inventory Tree') }}</h6>
            <span class="text-muted small">{{ __('Category->Products->Price / Stock') }}</span>
        </div>
        <div class="card-body">
            @forelse ($inventoryTree as $groupIndex => $group)
                <div class="accordion mb-2" id="inventory-group-{{ $groupIndex }}">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading-{{ $groupIndex }}">
                            <button class="accordion-button {{ $groupIndex > 0 ? 'collapsed' : '' }}" type="button"
                                data-bs-toggle="collapse" data-bs-target="#collapse-{{ $groupIndex }}"
                                aria-expanded="{{ $groupIndex === 0 ? 'true' : 'false' }}"
                                aria-controls="collapse-{{ $groupIndex }}">
                                <span class="fw-semibold">{{ $group['category_name'] }}</span>
                                <span class="badge bg-primary-transparent ms-2">{{ $group['products_count'] }}
                                    {{ __('Products') }}</span>
                            </button>
                        </h2>
                        <div id="collapse-{{ $groupIndex }}"
                            class="accordion-collapse collapse {{ $groupIndex === 0 ? 'show' : '' }}"
                            aria-labelledby="heading-{{ $groupIndex }}">
                            <div class="accordion-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-striped align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Product') }}</th>
                                                <th>{{ __('Brand / Unit') }}</th>
                                                <th class="text-end">{{ __('Cost') }}</th>
                                                <th class="text-end">{{ __('Retail') }}</th>
                                                <th class="text-end">{{ __('Wholesale') }}</th>
                                                <th class="text-end">{{ __('On Hand') }}</th>
                                                <th class="text-end">{{ __('Reserved') }}</th>
                                                <th class="text-end">{{ __('Available') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($group['products'] as $row)
                                                @php
                                                    $product = $row['product'];
                                                    $price = $row['latest_price'];
                                                @endphp
                                                <tr>
                                                    <td>
                                                        <div class="fw-semibold">{{ $product->name }}</div>
                                                        <div class="small text-muted">
                                                            {{ __('SKU') }}: {{ $product->sku ?: '-' }}
                                                            |
                                                            {{ __('Part#') }}: {{ $product->part_number ?: '-' }}
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div>{{ $product->brand?->name ?: '-' }}</div>
                                                        <div class="small text-muted">
                                                            {{ $product->defaultUnit?->name ?: '-' }}
                                                        </div>
                                                    </td>
                                                    <td class="text-end">
                                                        {{ $price ? $formatNumber((float) $price->cost) : '-' }}
                                                    </td>
                                                    <td class="text-end">
                                                        {{ $price ? $formatNumber((float) $price->retail_price) : '-' }}
                                                    </td>
                                                    <td class="text-end">
                                                        {{ $price ? $formatNumber((float) $price->wholesale_price) : '-' }}
                                                    </td>
                                                    <td class="text-end">{{ $formatNumber($row['qty_on_hand'], 0) }}</td>
                                                    <td class="text-end">{{ $formatNumber($row['qty_reserved'], 0) }}</td>
                                                    <td class="text-end">{{ $formatNumber($row['qty_available'], 0) }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="8" class="text-center text-muted py-4">
                                                        {{ __('No products in this category.') }}</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-muted text-center py-4">{{ __('No inventory records found for current filters.') }}</div>
            @endforelse
        </div>
    </div>
@endsection

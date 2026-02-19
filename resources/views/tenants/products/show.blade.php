@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Catalog')],
            ['label' => __('Products'), 'url' => route('tenant.products.index')],
            ['label' => __('Details')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Product Details') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.products.edit', $product) }}" class="btn btn-outline-primary">{{ __('Edit') }}</a>
            <a href="{{ route('tenant.products.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    @php
        $status = $product->status->value;
        $statusClasses = [
            'active' => 'bg-success-transparent',
            'inactive' => 'bg-secondary-transparent',
            'archived' => 'bg-warning-transparent',
        ];
        $statusClass = $statusClasses[$status] ?? 'bg-secondary-transparent';
        $tax = $product->defaultTax;
        $taxSource = $tax ? __('Product Default Tax') : null;
        $warehouseLabel = $branch?->warehouse?->name ?? __('Current Warehouse');
        $rowCount = max($priceRows->count(), 1);
        $activeCount = $status === 'active' ? $rowCount : 0;
        $inactiveCount = $rowCount - $activeCount;
    @endphp

    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-7">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <div>
                            <div class="text-muted small">{{ __('Product') }}</div>
                            <div class="fw-semibold fs-5">{{ $product->name }}</div>
                            <div class="text-muted small">{{ __('SKU') }}</div>
                            <div class="fw-semibold">{{ $product->sku ?? '-' }}</div>
                        </div>
                        <span class="badge {{ $statusClass }}">{{ ucfirst($status) }}</span>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="text-muted small">{{ __('Category') }}</div>
                            <div class="fw-semibold">{{ $product->category?->name ?? '-' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small">{{ __('Brand') }}</div>
                            <div class="fw-semibold">{{ $product->brand?->name ?? '-' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small">{{ __('Unit') }}</div>
                            <div class="fw-semibold">{{ $product->defaultUnit?->name ?? '-' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small">{{ __('Track Stock') }}</div>
                            <div class="fw-semibold">{{ $product->track_stock ? __('Yes') : __('No') }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small">{{ __('Part Number') }}</div>
                            <div class="fw-semibold">{{ $product->part_number ?? '-' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small">{{ __('Barcode') }}</div>
                            <div class="fw-semibold">{{ $product->barcode ?? '-' }}</div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">{{ __('Tax Rule') }}</div>
                                @if ($tax)
                                    <div class="fw-semibold">
                                        {{ $tax->name }} ({{ $tax->rate }}%)
                                    </div>
                                    <div class="text-muted small">{{ __('Source:') }} {{ $taxSource }}</div>
                                @else
                                    <div class="fw-semibold">-</div>
                                @endif
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">{{ __('Price Records') }}</div>
                                <div class="fw-semibold fs-5">{{ $priceRows->count() }}</div>
                                <div class="text-muted small mt-1">{{ __('Current Sale Price') }}:
                                    {{ number_format((float) ($latestPrice?->retail_price ?? 0), 2) }}</div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div>
                        <div class="text-muted small">{{ __('Description') }}</div>
                        <div class="fw-semibold">{{ $product->description ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card custom-card border-0 shadow-sm">
                <div class="card-header">
                    <h6 class="mb-0">{{ __('Variant Overview') }}</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">{{ __('Price Records') }}</span>
                        <span class="fw-semibold">{{ $rowCount }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">{{ __('Active Entries') }}</span>
                        <span class="fw-semibold">{{ $activeCount }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">{{ __('Inactive/Other') }}</span>
                        <span class="fw-semibold">{{ $inactiveCount }}</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">{{ __('On Hand') }}</span>
                        <span class="fw-semibold">{{ number_format($stockOnHand, 0) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">{{ __('Reserved') }}</span>
                        <span class="fw-semibold">{{ number_format($stockReserved, 0) }}</span>
                    </div>
                    <div class="text-muted small">{{ __('Stock Source') }}</div>
                    <div class="fw-semibold">{{ $warehouseLabel }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-header">
            <h6 class="mb-0">{{ __('Variants') }}</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('SKU') }}</th>
                            <th class="text-end">{{ __('On Hand') }}</th>
                            <th class="text-end">{{ __('Cost Price') }}</th>
                            <th class="text-end">{{ __('Sale Price') }}</th>
                            <th>{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($priceRows as $priceRow)
                            <tr>
                                <td class="fw-semibold">{{ $priceRow['name'] }}</td>
                                <td>{{ $priceRow['sku'] }}</td>
                                <td class="text-end">{{ number_format($priceRow['on_hand'], 0) }}</td>
                                <td class="text-end">{{ number_format($priceRow['cost'], 2) }}</td>
                                <td class="text-end">{{ number_format($priceRow['sale'], 2) }}</td>
                                <td>
                                    {{ ucfirst($priceRow['status']) }}
                                    @if ($priceRow['effective_from'])
                                        <div class="small text-muted">{{ __('Effective:') }}
                                            {{ $priceRow['effective_from'] }}</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="fw-semibold">{{ $product->name }}</td>
                                <td>{{ $product->sku ?? '-' }}</td>
                                <td class="text-end">{{ number_format($stockOnHand, 0) }}</td>
                                <td class="text-end">{{ number_format((float) ($latestPrice?->cost ?? 0), 2) }}</td>
                                <td class="text-end">{{ number_format((float) ($latestPrice?->retail_price ?? 0), 2) }}
                                </td>
                                <td>{{ ucfirst($status) }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

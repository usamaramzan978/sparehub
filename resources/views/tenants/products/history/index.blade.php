@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Catalog')],
            ['label' => __('Products')],
            ['label' => __('History')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Product History') }}" :items="$breadcrumbs" />

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('tenant.products.history') }}" class="row g-3">
                <div class="col-12 col-lg-10">
                    <label class="form-label" for="product_id">{{ __('Select Product') }}</label>
                    <select id="product_id" name="product_id" class="form-select singl-select-2">
                        <option value="">{{ __('Choose product') }}</option>
                        @foreach ($products as $productOption)
                            <option value="{{ $productOption->id }}" @selected($selectedProductId === $productOption->id)>
                                {{ $productOption->name }} ({{ $productOption->sku }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-lg-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100">{{ __('Show') }}</button>
                    <a href="{{ route('tenant.products.history') }}" class="btn btn-outline-secondary w-100">{{ __('Reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    @if ($selectedProduct && $summary)
        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card custom-card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">{{ __('Sales Count') }}</div>
                        <div class="fs-4 fw-semibold">{{ $summary['sales_count'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card custom-card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">{{ __('Sold Qty') }}</div>
                        <div class="fs-4 fw-semibold">{{ number_format($summary['sold_qty'], 3) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card custom-card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">{{ __('Sales Amount') }}</div>
                        <div class="fs-4 fw-semibold">{{ number_format($summary['sales_amount'], 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card custom-card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">{{ __('On Hand / Reserved') }}</div>
                        <div class="fs-4 fw-semibold">
                            {{ number_format($summary['qty_on_hand'], 3) }} / {{ number_format($summary['qty_reserved'], 3) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-6">
                <div class="card custom-card border-0 shadow-sm h-100">
                    <div class="card-header">
                        <h6 class="card-title mb-0">{{ __('Stock Movement Totals') }}</h6>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-7">{{ __('Opening Qty') }}</dt>
                            <dd class="col-5 text-end">{{ number_format($summary['opening_qty'], 3) }}</dd>
                            <dt class="col-7">{{ __('Purchased Qty') }}</dt>
                            <dd class="col-5 text-end">{{ number_format($summary['purchased_qty'], 3) }}</dd>
                            <dt class="col-7">{{ __('Purchase Return Qty') }}</dt>
                            <dd class="col-5 text-end">{{ number_format($summary['purchase_return_qty'], 3) }}</dd>
                            <dt class="col-7">{{ __('Adjustment In Qty') }}</dt>
                            <dd class="col-5 text-end">{{ number_format($summary['adjusted_in_qty'], 3) }}</dd>
                            <dt class="col-7">{{ __('Adjustment Out Qty') }}</dt>
                            <dd class="col-5 text-end">{{ number_format($summary['adjusted_out_qty'], 3) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-6">
                <div class="card custom-card border-0 shadow-sm h-100">
                    <div class="card-header">
                        <h6 class="card-title mb-0">{{ __('Selected Product') }}</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-2"><strong>{{ __('Name') }}:</strong> {{ $selectedProduct->name }}</div>
                        <div class="mb-2"><strong>{{ __('SKU') }}:</strong> {{ $selectedProduct->sku ?: '-' }}</div>
                        <div class="mb-2"><strong>{{ __('Part Number') }}:</strong> {{ $selectedProduct->part_number ?: '-' }}</div>
                        <div class="mb-2"><strong>{{ __('Status') }}:</strong> {{ ucfirst($selectedProduct->status->value) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h6 class="card-title mb-0">{{ __('Recent Sales For Product') }}</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('Invoice') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th class="text-end">{{ __('Qty') }}</th>
                                <th class="text-end">{{ __('Line Total') }}</th>
                                <th>{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentSales as $saleItem)
                                <tr>
                                    <td>{{ $saleItem->sale?->invoice_no ?? '-' }}</td>
                                    <td>{{ \App\Support\TenantDateTime::format($saleItem->sale?->invoice_date, 'Y-m-d', '-') }}</td>
                                    <td class="text-end">{{ number_format((float) $saleItem->qty, 3) }}</td>
                                    <td class="text-end">{{ number_format((float) $saleItem->line_total, 2) }}</td>
                                    <td>{{ ucfirst((string) ($saleItem->sale?->status?->value ?? '-')) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">{{ __('No sales found for this product.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">{{ __('Recent Stock Moves') }}</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('When') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th class="text-end">{{ __('Qty') }}</th>
                                <th>{{ __('By') }}</th>
                                <th>{{ __('Remarks') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentStockMoves as $move)
                                <tr>
                                    <td>{{ \App\Support\TenantDateTime::format($move->occurred_at, 'Y-m-d H:i', '-') }}</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', (string) $move->move_type?->value)) }}</td>
                                    <td class="text-end">{{ number_format((float) $move->qty, 3) }}</td>
                                    <td>{{ $move->creator?->name ?? '-' }}</td>
                                    <td>{{ $move->remarks ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">{{ __('No stock moves found for this product.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <div class="card custom-card border-0 shadow-sm">
            <div class="card-body text-muted">
                {{ __('Select a product to view its sales and stock history.') }}
            </div>
        </div>
    @endif
@endsection

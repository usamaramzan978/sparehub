@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [['label' => __('Reports')], ['label' => __('Vendor Products')]];
    @endphp

    <x-breadcrumb title="{{ __('Vendor Product Sales Report') }}" :items="$breadcrumbs"></x-breadcrumb>

    @include('tenants.reports.partials.filters', [
        'actionRouteName' => 'tenant.reports.vendor-products',
        'enabledFilters' => [
            'date_range',
            'vendor',
            'search',
        ],
    ])

    <div class="card custom-card border-0 shadow-sm h-100 mt-3">
        <div class="card-header">
            <h6 class="mb-0">{{ __('Top Running Vendor Products') }}</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Rank') }}</th>
                            <th>{{ __('Vendor') }}</th>
                            <th>{{ __('Product') }}</th>
                            <th>{{ __('SKU') }}</th>
                            <th class="text-end">{{ __('Sold Qty') }}</th>
                            <th class="text-end">{{ __('Sales Amount') }}</th>
                            <th class="text-end">{{ __('Invoices') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($vendorProductSales as $item)
                            <tr>
                                <td>{{ ($vendorProductSales->firstItem() ?? 1) + $loop->index }}</td>
                                <td>{{ $item->vendor_name ?? __('Unassigned') }}</td>
                                <td>{{ $item->product_name }}</td>
                                <td>{{ $item->product_sku }}</td>
                                <td class="text-end">{{ number_format((float) $item->total_qty_sold, 3) }}</td>
                                <td class="text-end">{{ number_format((float) $item->total_sales_amount, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $item->invoices_count, 0) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">{{ __('No vendor product sales found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $vendorProductSales->links() }}</div>
        </div>
    </div>
@endsection

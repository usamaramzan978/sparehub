@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [['label' => __('Reports')], ['label' => __('Category Sales')]];
    @endphp

    <x-breadcrumb title="{{ __('Category-wise Sales Report') }}" :items="$breadcrumbs"></x-breadcrumb>

    @include('tenants.reports.partials.filters', [
        'actionRouteName' => 'tenant.reports.category-sales',
        'enabledFilters' => [
            'date_range',
            'sale_status',
            'invoice_type',
            'search',
        ],
    ])

    <div class="card custom-card border-0 shadow-sm h-100 mt-3">
        <div class="card-header">
            <h6 class="mb-0">{{ __('Top Running Categories') }}</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Rank') }}</th>
                            <th>{{ __('Category') }}</th>
                            <th class="text-end">{{ __('Products') }}</th>
                            <th class="text-end">{{ __('Sold Qty') }}</th>
                            <th class="text-end">{{ __('Sales Amount') }}</th>
                            <th class="text-end">{{ __('Invoices') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categorySales as $item)
                            <tr>
                                <td>{{ ($categorySales->firstItem() ?? 1) + $loop->index }}</td>
                                <td>{{ $item->category_name ?? __('Uncategorized') }}</td>
                                <td class="text-end">{{ number_format((float) $item->products_count, 0) }}</td>
                                <td class="text-end">{{ number_format((float) $item->total_qty_sold, 3) }}</td>
                                <td class="text-end">{{ number_format((float) $item->total_sales_amount, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $item->invoices_count, 0) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">{{ __('No category sales found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $categorySales->links() }}</div>
        </div>
    </div>
@endsection

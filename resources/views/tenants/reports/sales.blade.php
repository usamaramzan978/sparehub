@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [['label' => __('Reports')], ['label' => __('Sales')]];
    @endphp

    <x-breadcrumb title="{{ __('Sales Report') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.reports.export.sales-pdf', request()->query()) }}" target="_blank"
                class="btn btn-primary btn-wave waves-effect waves-light">
                <i class="ri-upload-2-line me-2"></i> {{ __('Export PDF') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    @include('tenants.reports.partials.filters', [
        'actionRouteName' => 'tenant.reports.sales',
        'enabledFilters' => [
            'date_range',
            'sale_status',
            'invoice_type',
            'customer',
            'min_total',
            'max_total',
            'search',
        ],
    ])

    <div class="card custom-card border-0 shadow-sm h-100 mt-3">
        <div class="card-header">
            <h6 class="mb-0">{{ __('Sales Invoices Report') }}</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Invoice') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="text-end">{{ __('Grand Total') }}</th>
                            <th class="text-end">{{ __('Balance') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sales as $sale)
                            <tr>
                                <td>{{ $sale->invoice_no }}</td>
                                <td>{{ $sale->invoice_date?->format('Y-m-d') }}</td>
                                <td>{{ $sale->customer?->name ?? '-' }}</td>
                                <td>{{ ucfirst($sale->invoice_type->value) }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $sale->status->value)) }}</td>
                                <td class="text-end">{{ number_format((float) $sale->grand_total, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $sale->balance_due, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">{{ __('No sales found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $sales->links() }}</div>
        </div>
    </div>
@endsection

@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [['label' => __('Reports')], ['label' => __('Overview')]];
    @endphp

    <x-breadcrumb title="{{ __('Reports Overview') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.reports.export.summary-pdf', request()->query()) }}" target="_blank"
                class="btn btn-outline-secondary">{{ __('Export Summary PDF') }}</a>
            <a href="{{ route('tenant.reports.export.pdf', request()->query()) }}" target="_blank"
                class="btn btn-primary btn-wave waves-effect waves-light">
                <i class="ri-upload-2-line me-2"></i> {{ __('Export PDF') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    @include('tenants.reports.partials.filters', [
        'actionRouteName' => 'tenant.reports.overview',
        'enabledFilters' => [
            'date_range',
            'sale_status',
            'purchase_status',
            'invoice_type',
            'payment_method',
            'customer',
            'vendor',
            'min_total',
            'max_total',
            'search',
        ],
    ])

    <div class="row g-3 mt-1 mb-3">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('Sales Total') }}</div>
                    <h5 class="mb-0">{{ number_format($summary['sales_total'], 2) }}</h5>
                    <small>{{ $summary['sales_count'] }} {{ __('invoices') }}</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('Purchases Total') }}</div>
                    <h5 class="mb-0">{{ number_format($summary['purchases_total'], 2) }}</h5>
                    <small>{{ $summary['purchases_count'] }} {{ __('orders') }}</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('Receivables') }}</div>
                    <h5 class="mb-0">{{ number_format($summary['receivables_total'], 2) }}</h5>
                    <small>{{ $summary['recoverable_invoices_count'] }} {{ __('invoices to recover') }}</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('Payables') }}</div>
                    <h5 class="mb-0">{{ number_format($summary['payables_total'], 2) }}</h5>
                    <small>{{ __('Outstanding purchases') }}</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('Sale Payments') }}</div>
                    <h5 class="mb-0">{{ number_format($summary['sale_payments_total'], 2) }}</h5>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('Sales Payment Status') }}</div>
                    <div class="small">{{ __('Paid') }}: {{ $summary['paid_sales_count'] }}</div>
                    <div class="small">{{ __('Partial') }}: {{ $summary['partial_sales_count'] }}</div>
                    <div class="small">{{ __('Unpaid') }}: {{ $summary['unpaid_sales_count'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('Vendor Payments') }}</div>
                    <h5 class="mb-0">{{ number_format($summary['vendor_payments_total'], 2) }}</h5>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('Sale Collections By Method') }}</div>
                    <div class="small">{{ __('Cash') }}: {{ number_format((float) ($summary['sale_payment_method_totals']['cash'] ?? 0), 2) }}</div>
                    <div class="small">{{ __('Bank') }}: {{ number_format((float) ($summary['sale_payment_method_totals']['bank'] ?? 0), 2) }}</div>
                    <div class="small">{{ __('Card') }}: {{ number_format((float) ($summary['sale_payment_method_totals']['card'] ?? 0), 2) }}</div>
                    <div class="small">{{ __('Wallet') }}: {{ number_format((float) ($summary['sale_payment_method_totals']['wallet'] ?? 0), 2) }}</div>
                    <div class="small">{{ __('Other') }}: {{ number_format((float) ($summary['sale_payment_method_totals']['other'] ?? 0), 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-header">
                    <h6 class="mb-0">{{ __('Recent Sales') }}</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Invoice') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Customer') }}</th>
                                    <th class="text-end">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse (collect($sales->items())->take(5) as $sale)
                                    <tr>
                                        <td>{{ $sale->invoice_no }}</td>
                                        <td>@tenantDate($sale->invoice_date, 'Y-m-d', '')</td>
                                        <td>{{ $sale->customer?->name ?? '-' }}</td>
                                        <td class="text-end">{{ number_format((float) $sale->grand_total, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">{{ __('No sales found.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-header">
                    <h6 class="mb-0">{{ __('Recent Purchases') }}</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Purchase') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Vendor') }}</th>
                                    <th class="text-end">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse (collect($purchases->items())->take(5) as $purchase)
                                    <tr>
                                        <td>{{ $purchase->purchase_no }}</td>
                                        <td>@tenantDate($purchase->purchase_date, 'Y-m-d', '')</td>
                                        <td>{{ $purchase->vendor?->name ?? '-' }}</td>
                                        <td class="text-end">{{ number_format((float) $purchase->grand_total, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">{{ __('No purchases found.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

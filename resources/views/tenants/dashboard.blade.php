@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [['label' => __('Operations Overview')]];
        $formatCurrency = fn(float $value): string => number_format($value, 2);
        $growthBadge = function (float $value): string {
            if ($value > 0) {
                return 'text-success';
            }

            if ($value < 0) {
                return 'text-danger';
            }

            return 'text-muted';
        };
    @endphp

    <x-breadcrumb title="{{ __('Dashboard') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <form method="GET" action="{{ route('tenant.dashboard') }}"
                class="d-flex align-items-center gap-2 flex-wrap justify-content-end" id="dashboard-range-form">
                <input type="hidden" name="date_from" id="dashboard-date-from" value="{{ $dateRange['date_from'] }}">
                <input type="hidden" name="date_to" id="dashboard-date-to" value="{{ $dateRange['date_to'] }}">
                <div class="input-group border">
                    <div class="input-group-text bg-white border-0 pe-0"> <i class="ri-calendar-line lh-1"></i> </div>
                    <input type="text" class="form-control breadcrumb-input border-0 bg-white flatpickr-input"
                        id="daterange" name="date_range" value="{{ $dateRange['date_from'] }} to {{ $dateRange['date_to'] }}"
                        placeholder="Search By Date Range" readonly="readonly">
                </div>
                <button type="submit" class="btn btn-primary">{{ __('Apply') }}</button>
                <a href="{{ route('tenant.dashboard') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
            </form>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="row g-3">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <span class="text-muted small">{{ __('Sales') }}</span>
                    <h4 class="mt-2 mb-1">{{ $formatCurrency($summary['sales_total']) }}</h4>
                    <div class="small d-flex align-items-center justify-content-between">
                        <span class="text-muted">{{ $summary['sales_count'] }} {{ __('invoices') }}</span>
                        <span class="{{ $growthBadge($summary['sales_growth_percent']) }}">
                            {{ $summary['sales_growth_percent'] > 0 ? '+' : '' }}{{ number_format($summary['sales_growth_percent'], 2) }}%
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <span class="text-muted small">{{ __('Purchases') }}</span>
                    <h4 class="mt-2 mb-1">{{ $formatCurrency($summary['purchases_total']) }}</h4>
                    <div class="small d-flex align-items-center justify-content-between">
                        <span class="text-muted">{{ $summary['purchases_count'] }} {{ __('orders') }}</span>
                        <span class="{{ $growthBadge($summary['purchase_growth_percent']) }}">
                            {{ $summary['purchase_growth_percent'] > 0 ? '+' : '' }}{{ number_format($summary['purchase_growth_percent'], 2) }}%
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <span class="text-muted small">{{ __('Gross Margin') }}</span>
                    <h4 class="mt-2 mb-1">{{ $formatCurrency($summary['gross_margin_value']) }}</h4>
                    <div class="small d-flex align-items-center justify-content-between">
                        <span class="text-muted">{{ __('Margin Rate') }}</span>
                        <span class="{{ $growthBadge($summary['gross_margin_percent']) }}">
                            {{ number_format($summary['gross_margin_percent'], 2) }}%
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <span class="text-muted small">{{ __('Net Cash Flow') }}</span>
                    <h4 class="mt-2 mb-1">{{ $formatCurrency($summary['cashflow_net']) }}</h4>
                    <div class="small d-flex align-items-center justify-content-between">
                        <span class="text-muted">{{ __('In - Out') }}</span>
                        <span class="{{ $growthBadge($summary['cashflow_net']) }}">
                            {{ __('In') }} {{ $formatCurrency($summary['sale_payments_total']) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-12 col-xl-8">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="card-title mb-0">{{ __('Sales vs Purchases Trend') }}</h6>
                    <span class="text-muted small">{{ $dateRange['label'] }}</span>
                </div>
                <div class="card-body">
                    <div id="dashboard-sales-purchase-trend" style="min-height: 320px;"></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card custom-card border-0 shadow-sm mb-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">{{ __('Payment Mix') }}</h6>
                </div>
                <div class="card-body">
                    <div id="dashboard-payment-mix" style="min-height: 240px;"></div>
                </div>
            </div>

            <div class="card custom-card border-0 shadow-sm">
                <div class="card-header">
                    <h6 class="card-title mb-0">{{ __('Sale Status Breakdown') }}</h6>
                </div>
                <div class="card-body">
                    <div id="dashboard-sale-status" style="min-height: 220px;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-12 col-lg-4">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="card-title mb-0">{{ __('Top Customers') }}</h6>
                    <span class="badge bg-primary-transparent">{{ __('By Sales') }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Customer') }}</th>
                                    <th class="text-end">{{ __('Invoices') }}</th>
                                    <th class="text-end">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($topCustomers as $customer)
                                    <tr>
                                        <td>{{ $customer->customer?->name ?? __('Unknown') }}</td>
                                        <td class="text-end">{{ (int) $customer->invoice_count }}</td>
                                        <td class="text-end">{{ $formatCurrency((float) $customer->sales_total) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">
                                            {{ __('No customer sales in selected range.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="card-title mb-0">{{ __('Top Vendors') }}</h6>
                    <span class="badge bg-warning-transparent">{{ __('By Purchases') }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Vendor') }}</th>
                                    <th class="text-end">{{ __('Orders') }}</th>
                                    <th class="text-end">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($topVendors as $vendor)
                                    <tr>
                                        <td>{{ $vendor->vendor?->name ?? __('Unknown') }}</td>
                                        <td class="text-end">{{ (int) $vendor->purchase_count }}</td>
                                        <td class="text-end">{{ $formatCurrency((float) $vendor->purchases_total) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">
                                            {{ __('No vendor purchases in selected range.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="card-title mb-0">{{ __('Top Stock') }}</h6>
                    <span class="badge bg-info-transparent">{{ __('By Quantity') }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Product') }}</th>
                                    <th class="text-end">{{ __('On Hand') }}</th>
                                    <th class="text-end">{{ __('Reserved') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($topStockItems as $stock)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $stock->product?->name ?? __('Unknown') }}</div>
                                            <small class="text-muted">{{ $stock->product?->sku ?: __('No SKU') }}</small>
                                        </td>
                                        <td class="text-end fw-semibold">
                                            {{ number_format((float) $stock->qty_on_hand, 0) }}</td>
                                        <td class="text-end">{{ number_format((float) $stock->qty_reserved, 0) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">
                                            {{ __('No stock records.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="card-title mb-0">{{ __('Stock Alerts') }}</h6>
                    <span class="badge bg-danger-transparent">{{ __('Low Stock') }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Product') }}</th>
                                    <th class="text-end">{{ __('On Hand') }}</th>
                                    <th class="text-end">{{ __('Reserved') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($lowStockItems as $stock)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $stock->product?->name ?? __('Unknown') }}</div>
                                            <small class="text-muted">{{ $stock->product?->sku ?: __('No SKU') }}</small>
                                        </td>
                                        <td class="text-end text-danger">
                                            {{ number_format((float) $stock->qty_on_hand, 0) }}</td>
                                        <td class="text-end">{{ number_format((float) $stock->qty_reserved, 0) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">
                                            {{ __('No low stock alerts.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-12 col-xl-6">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="card-title mb-0">{{ __('Recent Sales') }}</h6>
                    <a href="{{ route('tenant.sales.index') }}"
                        class="btn btn-sm btn-outline-primary">{{ __('View All') }}</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Invoice') }}</th>
                                    <th>{{ __('Customer') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th class="text-end">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentSales as $sale)
                                    <tr>
                                        <td>{{ $sale->invoice_no }}</td>
                                        <td>{{ $sale->customer?->name ?? '-' }}</td>
                                        <td>{{ $sale->invoice_date?->format('Y-m-d') }}</td>
                                        <td class="text-end">{{ $formatCurrency((float) $sale->grand_total) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            {{ __('No sales for selected range.') }}</td>
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
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="card-title mb-0">{{ __('Recent Purchases') }}</h6>
                    <a href="{{ route('tenant.purchases.index') }}"
                        class="btn btn-sm btn-outline-primary">{{ __('View All') }}</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Purchase') }}</th>
                                    <th>{{ __('Vendor') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th class="text-end">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentPurchases as $purchase)
                                    <tr>
                                        <td>{{ $purchase->purchase_no }}</td>
                                        <td>{{ $purchase->vendor?->name ?? '-' }}</td>
                                        <td>{{ $purchase->purchase_date?->format('Y-m-d') }}</td>
                                        <td class="text-end">{{ $formatCurrency((float) $purchase->grand_total) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            {{ __('No purchases for selected range.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1 mb-3">
        <div class="col-12 col-md-4">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <span class="text-muted small">{{ __('Receivables') }}</span>
                    <h5 class="mt-2 mb-0">{{ $formatCurrency($summary['receivables_total']) }}</h5>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <span class="text-muted small">{{ __('Payables') }}</span>
                    <h5 class="mt-2 mb-0">{{ $formatCurrency($summary['payables_total']) }}</h5>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <span class="text-muted small">{{ __('Operational Pulse') }}</span>
                    <h6 class="mt-2 mb-1">{{ $summary['active_customers_count'] }} {{ __('active customers') }}</h6>
                    <div class="small text-muted">{{ $summary['active_vendors_count'] }} {{ __('active vendors') }} ·
                        {{ $summary['open_job_cards_count'] }} {{ __('open job cards') }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script id="dashboard-chart-data" type="application/json">@json($chartData)</script>
    @vite(['resources/js/dashboard.js'])
@endpush

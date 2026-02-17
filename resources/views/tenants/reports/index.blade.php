@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('System')],
            ['label' => __('Reports')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Reports') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.reports.export.summary-pdf', request()->query()) }}" target="_blank"
                class="btn btn-outline-secondary">{{ __('Export Summary PDF') }}</a>
            <a href="{{ route('tenant.reports.export.pdf', request()->query()) }}" target="_blank"
                class="btn btn-outline-danger">{{ __('Export PDF') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('tenant.reports.index') }}" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label" for="date_from">{{ __('Date From') }}</label>
                    <input type="date" id="date_from" name="date_from" class="form-control"
                        value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="date_to">{{ __('Date To') }}</label>
                    <input type="date" id="date_to" name="date_to" class="form-control"
                        value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="sale_status">{{ __('Sale Status') }}</label>
                    <select id="sale_status" name="sale_status" class="form-select singl-select-2">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($saleStatuses as $status)
                            <option value="{{ $status->value }}" @selected(request('sale_status') === $status->value)>
                                {{ ucfirst(str_replace('_', ' ', $status->value)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="purchase_status">{{ __('Purchase Status') }}</label>
                    <select id="purchase_status" name="purchase_status" class="form-select singl-select-2">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($purchaseStatuses as $status)
                            <option value="{{ $status->value }}" @selected(request('purchase_status') === $status->value)>
                                {{ ucfirst(str_replace('_', ' ', $status->value)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="invoice_type">{{ __('Invoice Type') }}</label>
                    <select id="invoice_type" name="invoice_type" class="form-select singl-select-2">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($invoiceTypes as $type)
                            <option value="{{ $type->value }}" @selected(request('invoice_type') === $type->value)>
                                {{ ucfirst($type->value) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="payment_method">{{ __('Payment Method') }}</label>
                    <select id="payment_method" name="payment_method" class="form-select singl-select-2">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($paymentMethods as $method)
                            <option value="{{ $method->value }}" @selected(request('payment_method') === $method->value)>
                                {{ ucfirst($method->value) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="customer_id">{{ __('Customer') }}</label>
                    <select id="customer_id" name="customer_id" class="form-select singl-select-2">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected(request('customer_id') === $customer->id)>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="vendor_id">{{ __('Vendor') }}</label>
                    <select id="vendor_id" name="vendor_id" class="form-select singl-select-2">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($vendors as $vendor)
                            <option value="{{ $vendor->id }}" @selected(request('vendor_id') === $vendor->id)>
                                {{ $vendor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="min_total">{{ __('Min Total') }}</label>
                    <input type="number" step="0.01" id="min_total" name="min_total" class="form-control"
                        value="{{ request('min_total') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="max_total">{{ __('Max Total') }}</label>
                    <input type="number" step="0.01" id="max_total" name="max_total" class="form-control"
                        value="{{ request('max_total') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="search">{{ __('Search') }}</label>
                    <input type="text" id="search" name="search" class="form-control" value="{{ request('search') }}"
                        placeholder="{{ __('Invoice, vendor, ref') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100">{{ __('Apply') }}</button>
                    <a href="{{ route('tenant.reports.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card"><div class="card-body"><div class="text-muted small">{{ __('Sales Total') }}</div><h5 class="mb-0">{{ number_format($summary['sales_total'], 2) }}</h5><small>{{ $summary['sales_count'] }} {{ __('invoices') }}</small></div></div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card"><div class="card-body"><div class="text-muted small">{{ __('Purchases Total') }}</div><h5 class="mb-0">{{ number_format($summary['purchases_total'], 2) }}</h5><small>{{ $summary['purchases_count'] }} {{ __('orders') }}</small></div></div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card"><div class="card-body"><div class="text-muted small">{{ __('Receivables') }}</div><h5 class="mb-0">{{ number_format($summary['receivables_total'], 2) }}</h5><small>{{ __('Outstanding sales') }}</small></div></div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card"><div class="card-body"><div class="text-muted small">{{ __('Payables') }}</div><h5 class="mb-0">{{ number_format($summary['payables_total'], 2) }}</h5><small>{{ __('Outstanding purchases') }}</small></div></div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card"><div class="card-body"><div class="text-muted small">{{ __('Sale Payments') }}</div><h5 class="mb-0">{{ number_format($summary['sale_payments_total'], 2) }}</h5></div></div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card"><div class="card-body"><div class="text-muted small">{{ __('Vendor Payments') }}</div><h5 class="mb-0">{{ number_format($summary['vendor_payments_total'], 2) }}</h5></div></div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header"><h6 class="mb-0">{{ __('Sales Invoices Report') }}</h6></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead><tr><th>{{ __('Invoice') }}</th><th>{{ __('Date') }}</th><th>{{ __('Customer') }}</th><th>{{ __('Type') }}</th><th>{{ __('Status') }}</th><th class="text-end">{{ __('Grand Total') }}</th><th class="text-end">{{ __('Balance') }}</th></tr></thead>
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
                            <tr><td colspan="7" class="text-center text-muted">{{ __('No sales found.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $sales->links() }}</div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header"><h6 class="mb-0">{{ __('Purchase Invoices Report') }}</h6></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead><tr><th>{{ __('Purchase') }}</th><th>{{ __('Date') }}</th><th>{{ __('Vendor') }}</th><th>{{ __('Status') }}</th><th class="text-end">{{ __('Grand Total') }}</th><th class="text-end">{{ __('Balance') }}</th></tr></thead>
                    <tbody>
                        @forelse ($purchases as $purchase)
                            <tr>
                                <td>{{ $purchase->purchase_no }}</td>
                                <td>{{ $purchase->purchase_date?->format('Y-m-d') }}</td>
                                <td>{{ $purchase->vendor?->name ?? '-' }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $purchase->status->value)) }}</td>
                                <td class="text-end">{{ number_format((float) $purchase->grand_total, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $purchase->balance_due, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">{{ __('No purchases found.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $purchases->links() }}</div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header"><h6 class="mb-0">{{ __('Sale Payments Report') }}</h6></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead><tr><th>{{ __('Invoice') }}</th><th>{{ __('Method') }}</th><th>{{ __('Paid At') }}</th><th>{{ __('Reference') }}</th><th class="text-end">{{ __('Amount') }}</th></tr></thead>
                    <tbody>
                        @forelse ($salePayments as $payment)
                            <tr>
                                <td>{{ $payment->sale?->invoice_no ?? '-' }}</td>
                                <td>{{ ucfirst($payment->payment_method->value) }}</td>
                                <td>{{ $payment->paid_at?->format('Y-m-d H:i') }}</td>
                                <td>{{ $payment->reference_no ?: '-' }}</td>
                                <td class="text-end">{{ number_format((float) $payment->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">{{ __('No sale payments found.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $salePayments->links() }}</div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header"><h6 class="mb-0">{{ __('Vendor Payments Report') }}</h6></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead><tr><th>{{ __('Payment No') }}</th><th>{{ __('Vendor') }}</th><th>{{ __('Method') }}</th><th>{{ __('Paid At') }}</th><th class="text-end">{{ __('Amount') }}</th></tr></thead>
                    <tbody>
                        @forelse ($vendorPayments as $payment)
                            <tr>
                                <td>{{ $payment->payment_no }}</td>
                                <td>{{ $payment->vendor?->name ?? '-' }}</td>
                                <td>{{ ucfirst($payment->payment_method->value) }}</td>
                                <td>{{ $payment->paid_at?->format('Y-m-d H:i') }}</td>
                                <td class="text-end">{{ number_format((float) $payment->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">{{ __('No vendor payments found.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $vendorPayments->links() }}</div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">{{ __('Receivables Report') }}</h6></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead><tr><th>{{ __('Invoice') }}</th><th>{{ __('Customer') }}</th><th>{{ __('Date') }}</th><th class="text-end">{{ __('Balance') }}</th></tr></thead>
                            <tbody>
                                @forelse ($receivables as $sale)
                                    <tr>
                                        <td>{{ $sale->invoice_no }}</td>
                                        <td>{{ $sale->customer?->name ?? '-' }}</td>
                                        <td>{{ $sale->invoice_date?->format('Y-m-d') }}</td>
                                        <td class="text-end">{{ number_format((float) $sale->balance_due, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted">{{ __('No receivables found.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">{{ $receivables->links() }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">{{ __('Payables Report') }}</h6></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead><tr><th>{{ __('Purchase') }}</th><th>{{ __('Vendor') }}</th><th>{{ __('Date') }}</th><th class="text-end">{{ __('Balance') }}</th></tr></thead>
                            <tbody>
                                @forelse ($payables as $purchase)
                                    <tr>
                                        <td>{{ $purchase->purchase_no }}</td>
                                        <td>{{ $purchase->vendor?->name ?? '-' }}</td>
                                        <td>{{ $purchase->purchase_date?->format('Y-m-d') }}</td>
                                        <td class="text-end">{{ number_format((float) $purchase->balance_due, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted">{{ __('No payables found.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">{{ $payables->links() }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection

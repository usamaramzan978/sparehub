@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [['label' => __('Sales')], ['label' => __('Sales Tree')]];
        $money = fn(float $value): string => number_format($value, 2);
        $qty = fn(float $value): string => number_format($value, 3);
    @endphp

    <x-breadcrumb title="{{ __('Sales Tree') }}" :items="$breadcrumbs" />

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('tenant.sales-tree.index') }}" class="row g-2">
                <div class="col-md-2">
                    <label class="form-label" for="date_from">{{ __('Date From') }}</label>
                    <input type="date" class="form-control" id="date_from" name="date_from"
                        value="{{ $filters['date_from'] }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="date_to">{{ __('Date To') }}</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="{{ $filters['date_to'] }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="customer_id">{{ __('Customer') }}</label>
                    <select class="form-select" id="customer_id" name="customer_id">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected($filters['customer_id'] === $customer->id)>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="status">{{ __('Status') }}</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ ucfirst(str_replace('_', ' ', $status->value)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="search">{{ __('Search') }}</label>
                    <input type="text" class="form-control" id="search" name="search" value="{{ $filters['search'] }}"
                        placeholder="{{ __('Invoice, customer, product') }}">
                </div>
                <div class="col-12 d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                    <a href="{{ route('tenant.sales-tree.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-4"><div class="card"><div class="card-body"><div class="text-muted small">{{ __('Customers') }}</div><h5 class="mb-0">{{ $summary['customers_count'] }}</h5></div></div></div>
        <div class="col-12 col-md-4"><div class="card"><div class="card-body"><div class="text-muted small">{{ __('Invoices') }}</div><h5 class="mb-0">{{ $summary['invoices_count'] }}</h5></div></div></div>
        <div class="col-12 col-md-4"><div class="card"><div class="card-body"><div class="text-muted small">{{ __('Items') }}</div><h5 class="mb-0">{{ $summary['items_count'] }}</h5></div></div></div>
        <div class="col-12 col-md-4"><div class="card"><div class="card-body"><div class="text-muted small">{{ __('Total Sales') }}</div><h5 class="mb-0">{{ $money($summary['grand_total']) }}</h5></div></div></div>
        <div class="col-12 col-md-4"><div class="card"><div class="card-body"><div class="text-muted small">{{ __('Paid') }}</div><h5 class="mb-0">{{ $money($summary['paid_total']) }}</h5></div></div></div>
        <div class="col-12 col-md-4"><div class="card"><div class="card-body"><div class="text-muted small">{{ __('Balance Due') }}</div><h5 class="mb-0">{{ $money($summary['balance_due']) }}</h5></div></div></div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0">{{ __('Sales Tree') }}</h6>
            <span class="text-muted small">{{ __('Customer -> Invoices -> Items') }}</span>
        </div>
        <div class="card-body">
            @forelse ($groupedSales as $index => $group)
                <div class="accordion mb-2" id="sales-group-{{ $index }}">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="sales-heading-{{ $index }}">
                            <button class="accordion-button {{ $index > 0 ? 'collapsed' : '' }}" type="button"
                                data-bs-toggle="collapse" data-bs-target="#sales-collapse-{{ $index }}">
                                <span class="fw-semibold">{{ $group['customer_name'] }}</span>
                                <span class="badge bg-primary-transparent ms-2">{{ $group['invoices_count'] }}
                                    {{ __('Invoices') }}</span>
                                <span class="ms-2 text-muted">{{ __('Total:') }} {{ $money($group['grand_total']) }}</span>
                            </button>
                        </h2>
                        <div id="sales-collapse-{{ $index }}"
                            class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}">
                            <div class="accordion-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-striped align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Invoice') }}</th>
                                                <th>{{ __('Date') }}</th>
                                                <th>{{ __('Status') }}</th>
                                                <th class="text-end">{{ __('Items') }}</th>
                                                <th class="text-end">{{ __('Qty') }}</th>
                                                <th class="text-end">{{ __('Grand Total') }}</th>
                                                <th class="text-end">{{ __('Paid') }}</th>
                                                <th class="text-end">{{ __('Balance') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($group['invoices'] as $invoiceRow)
                                                @php $sale = $invoiceRow['sale']; @endphp
                                                <tr>
                                                    <td>
                                                        <div class="fw-semibold">{{ $sale->invoice_no }}</div>
                                                        <div class="small text-muted">
                                                            @foreach ($sale->items as $line)
                                                                <div>
                                                                    {{ $line->product?->name ?? $line->serviceCatalog?->name ?? ($line->description ?: '-') }}
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </td>
                                                    <td>{{ $sale->invoice_date?->format('Y-m-d') ?: '-' }}</td>
                                                    <td>{{ ucfirst(str_replace('_', ' ', $sale->status->value)) }}</td>
                                                    <td class="text-end">{{ $invoiceRow['items_count'] }}</td>
                                                    <td class="text-end">{{ $qty($invoiceRow['qty_total']) }}</td>
                                                    <td class="text-end">{{ $money((float) $sale->grand_total) }}</td>
                                                    <td class="text-end">{{ $money((float) $sale->paid_total) }}</td>
                                                    <td class="text-end">{{ $money((float) $sale->balance_due) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-4">{{ __('No sales found for selected filters.') }}</div>
            @endforelse
        </div>
    </div>
@endsection

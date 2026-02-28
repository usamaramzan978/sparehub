@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Sales')],
            ['label' => __('Sale Invoices'), 'url' => route('tenant.sales.index')],
            ['label' => __('Details')],
        ];

        $statusClass = match ($sale->status->value) {
            'posted' => 'bg-success-transparent',
            'draft' => 'bg-secondary-transparent',
            'hold' => 'bg-warning-transparent',
            default => 'bg-secondary-transparent',
        };
        $onlinePaymentProofUrl = $sale->getFirstMediaUrl('online_payment_proof');
    @endphp

    <x-breadcrumb title="{{ __('Invoice Details') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.sales.print', ['sale' => $sale, 'auto_print' => 1]) }}" target="_blank"
                class="btn btn-outline-dark">{{ __('Print') }}</a>
            <a href="{{ route('tenant.sales.edit', $sale) }}" class="btn btn-outline-primary">{{ __('Edit') }}</a>
            <a href="{{ route('tenant.sales.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
                <div>
                    <div class="text-muted small">{{ __('Invoice') }}</div>
                    <h5 class="mb-1">{{ $sale->invoice_no }}</h5>
                    <div class="text-muted small">{{ __('Date') }}: @tenantDate($sale->invoice_date, 'Y-m-d')
                    </div>
                </div>
                <span class="badge {{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $sale->status->value)) }}</span>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Customer') }}</div>
                        <div class="fw-semibold">{{ $sale->customer?->name ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Job Card') }}</div>
                        <div class="fw-semibold">{{ $sale->jobCard?->job_no ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Invoice Type') }}</div>
                        <div class="fw-semibold">{{ ucfirst($sale->invoice_type->value) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Created By') }}</div>
                        <div class="fw-semibold">{{ $sale->creator?->name ?? '-' }}</div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-2">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Sub Total') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $sale->sub_total, 2) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Discount') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $sale->discount_total, 2) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Tax') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $sale->tax_total, 2) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Grand Total') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $sale->grand_total, 2) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Paid') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $sale->paid_total, 2) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Balance') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $sale->balance_due, 2) }}</div>
                    </div>
                </div>
            </div>

            @if ($sale->notes)
                <div class="border rounded p-3 mt-3">
                    <div class="text-muted small">{{ __('Notes') }}</div>
                    <div class="fw-semibold">{{ $sale->notes }}</div>
                </div>
            @endif

            @if ($onlinePaymentProofUrl !== '')
                <div class="border rounded p-3 mt-3">
                    <div class="text-muted small mb-2">{{ __('Online Payment Proof') }}</div>
                    <a href="{{ $onlinePaymentProofUrl }}" target="_blank" rel="noopener">
                        <img src="{{ $onlinePaymentProofUrl }}" alt="{{ __('Online Payment Proof') }}"
                            class="img-fluid rounded border" style="max-height: 280px;">
                    </a>
                </div>
            @endif
        </div>
    </div>

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-header">
            <h6 class="mb-0">{{ __('Sale Items') }}</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Item') }}</th>
                            <th class="text-end">{{ __('Qty') }}</th>
                            <th class="text-end">{{ __('Unit Price') }}</th>
                            <th class="text-end">{{ __('Discount') }}</th>
                            <th class="text-end">{{ __('Tax') }}</th>
                            <th class="text-end">{{ __('Line Total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sale->items as $saleItem)
                            <tr>
                                <td>{{ ucfirst($saleItem->line_type->value) }}</td>
                                <td>{{ $saleItem->description ?: $saleItem->product?->name ?? ($saleItem->serviceCatalog?->name ?? '-') }}
                                </td>
                                <td class="text-end">{{ number_format((float) $saleItem->qty, 3) }}</td>
                                <td class="text-end">{{ number_format((float) $saleItem->unit_price, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $saleItem->discount_amount, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $saleItem->tax_amount, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $saleItem->line_total, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">{{ __('No sale items found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-header">
            <h6 class="mb-0">{{ __('Sale Payments') }}</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Method') }}</th>
                            <th class="text-end">{{ __('Amount') }}</th>
                            <th>{{ __('Received By') }}</th>
                            <th>{{ __('Paid At') }}</th>
                            <th>{{ __('Reference') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sale->payments as $salePayment)
                            <tr>
                                <td>{{ ucfirst($salePayment->payment_method->value) }}</td>
                                <td class="text-end">{{ number_format((float) $salePayment->amount, 2) }}</td>
                                <td>{{ $salePayment->receiver?->name ?? '-' }}</td>
                                <td>@tenantDate($salePayment->paid_at, 'Y-m-d H:i')</td>
                                <td>{{ $salePayment->reference_no ?? '-' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('tenant.sale-payments.show', $salePayment) }}"
                                        class="btn btn-sm btn-icon btn-primary-light btn-wave waves-effect waves-light"
                                        title="{{ __('View') }}">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">{{ __('No payments found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

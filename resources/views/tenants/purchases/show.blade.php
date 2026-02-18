@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Purchases')],
            ['label' => __('Purchase Orders'), 'url' => route('tenant.purchases.index')],
            ['label' => __('Details')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Purchase Details') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.purchases.edit', $purchase) }}" class="btn btn-outline-primary">{{ __('Edit') }}</a>
            <a href="{{ route('tenant.purchases.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
                <div>
                    <div class="text-muted small">{{ __('Purchase No') }}</div>
                    <h5 class="mb-1">{{ $purchase->purchase_no }}</h5>
                    <div class="text-muted small">{{ __('Date') }}:
                        {{ $purchase->purchase_date?->format('Y-m-d') ?? '-' }}</div>
                </div>
                <div class="text-end">
                    <div class="text-muted small">{{ __('Status') }}</div>
                    <div class="fw-semibold">{{ ucfirst(str_replace('_', ' ', $purchase->status->value)) }}</div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Vendor') }}</div>
                        <div class="fw-semibold">{{ $purchase->vendor?->name ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Warehouse') }}</div>
                        <div class="fw-semibold">{{ $purchase->warehouse?->name ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Vendor Invoice No') }}</div>
                        <div class="fw-semibold">{{ $purchase->vendor_invoice_no ?: '-' }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Due Date') }}</div>
                        <div class="fw-semibold">{{ $purchase->due_date?->format('Y-m-d') ?? '-' }}</div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-2">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Sub Total') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $purchase->sub_total, 2) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Discount') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $purchase->discount_total, 2) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Tax') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $purchase->tax_total, 2) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Shipping') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $purchase->shipping_total, 2) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Grand Total') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $purchase->grand_total, 2) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Balance Due') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $purchase->balance_due, 2) }}</div>
                    </div>
                </div>
            </div>

            @if ($purchase->notes)
                <div class="border rounded p-3 mt-3">
                    <div class="text-muted small">{{ __('Notes') }}</div>
                    <div class="fw-semibold">{{ $purchase->notes }}</div>
                </div>
            @endif
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h6 class="mb-0">{{ __('Purchase Items') }}</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Product') }}</th>
                            <th class="text-end">{{ __('Qty') }}</th>
                            <th class="text-end">{{ __('Received') }}</th>
                            <th class="text-end">{{ __('Unit Cost') }}</th>
                            <th class="text-end">{{ __('Line Total') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($purchase->items as $purchaseItem)
                            <tr>
                                <td>{{ $purchaseItem->product?->name ?? '-' }}</td>
                                <td class="text-end">{{ number_format((float) $purchaseItem->qty, 3) }}</td>
                                <td class="text-end">{{ number_format((float) $purchaseItem->received_qty, 3) }}</td>
                                <td class="text-end">{{ number_format((float) $purchaseItem->unit_cost, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $purchaseItem->line_total, 2) }}</td>
                                <td class="text-end">
                                    <a href="{{ route('tenant.purchase-items.show', $purchaseItem) }}"
                                        class="btn btn-sm btn-icon btn-primary-light btn-wave waves-effect waves-light"
                                        title="{{ __('View') }}">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">{{ __('No purchase items found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h6 class="mb-0">{{ __('Vendor Payments') }}</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Payment No') }}</th>
                            <th>{{ __('Method') }}</th>
                            <th class="text-end">{{ __('Amount') }}</th>
                            <th>{{ __('Paid At') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($purchase->payments as $vendorPayment)
                            <tr>
                                <td>{{ $vendorPayment->payment_no }}</td>
                                <td>{{ ucfirst($vendorPayment->payment_method->value) }}</td>
                                <td class="text-end">{{ number_format((float) $vendorPayment->amount, 2) }}</td>
                                <td>{{ $vendorPayment->paid_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('tenant.vendor-payments.show', $vendorPayment) }}"
                                        class="btn btn-sm btn-icon btn-primary-light btn-wave waves-effect waves-light"
                                        title="{{ __('View') }}">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">{{ __('No vendor payments found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h6 class="mb-0">{{ __('Purchase Returns') }}</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Return No') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="text-end">{{ __('Grand Total') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($purchase->returns as $purchaseReturn)
                            <tr>
                                <td>{{ $purchaseReturn->return_no }}</td>
                                <td>{{ $purchaseReturn->return_date?->format('Y-m-d') ?? '-' }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $purchaseReturn->status->value)) }}</td>
                                <td class="text-end">{{ number_format((float) $purchaseReturn->grand_total, 2) }}</td>
                                <td class="text-end">
                                    <a href="{{ route('tenant.purchase-returns.show', $purchaseReturn) }}"
                                        class="btn btn-sm btn-icon btn-primary-light btn-wave waves-effect waves-light"
                                        title="{{ __('View') }}">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">{{ __('No purchase returns found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

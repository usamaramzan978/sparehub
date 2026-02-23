@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Purchases')],
            ['label' => __('Purchase Returns'), 'url' => route('tenant.purchase-returns.index')],
            ['label' => __('Details')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Purchase Return Details') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.purchase-returns.edit', $purchaseReturn) }}"
                class="btn btn-outline-primary">{{ __('Edit') }}</a>
            <a href="{{ route('tenant.purchase-returns.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
                <div>
                    <div class="text-muted small">{{ __('Return No') }}</div>
                    <h5 class="mb-1">{{ $purchaseReturn->return_no }}</h5>
                    <div class="text-muted small">{{ __('Date') }}:
                        @tenantDate($purchaseReturn->return_date, 'Y-m-d')</div>
                </div>
                <div class="text-end">
                    <div class="text-muted small">{{ __('Status') }}</div>
                    <div class="fw-semibold">{{ ucfirst(str_replace('_', ' ', $purchaseReturn->status->value)) }}</div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Vendor') }}</div>
                        <div class="fw-semibold">{{ $purchaseReturn->vendor?->name ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Purchase') }}</div>
                        <div class="fw-semibold">{{ $purchaseReturn->purchase?->purchase_no ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Sub Total') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $purchaseReturn->sub_total, 2) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Tax') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $purchaseReturn->tax_total, 2) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Grand Total') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $purchaseReturn->grand_total, 2) }}</div>
                    </div>
                </div>
            </div>

            @if ($purchaseReturn->notes)
                <div class="border rounded p-3 mt-3">
                    <div class="text-muted small">{{ __('Notes') }}</div>
                    <div class="fw-semibold">{{ $purchaseReturn->notes }}</div>
                </div>
            @endif
        </div>
    </div>

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-header">
            <h6 class="mb-0">{{ __('Return Items') }}</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Product') }}</th>
                            <th class="text-end">{{ __('Qty') }}</th>
                            <th class="text-end">{{ __('Unit Cost') }}</th>
                            <th class="text-end">{{ __('Tax') }}</th>
                            <th class="text-end">{{ __('Line Total') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($purchaseReturn->items as $returnItem)
                            <tr>
                                <td>{{ $returnItem->product?->name ?? '-' }}</td>
                                <td class="text-end">{{ number_format((float) $returnItem->qty, 3) }}</td>
                                <td class="text-end">{{ number_format((float) $returnItem->unit_cost, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $returnItem->tax_amount, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $returnItem->line_total, 2) }}</td>
                                <td class="text-end">
                                    <a href="{{ route('tenant.purchase-return-items.show', $returnItem) }}"
                                        class="btn btn-sm btn-icon btn-primary-light btn-wave waves-effect waves-light"
                                        title="{{ __('View') }}">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">{{ __('No return items found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

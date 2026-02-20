@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [['label' => __('Reports')], ['label' => __('Sale Payments')]];
    @endphp

    <x-breadcrumb title="{{ __('Sale Payments Report') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.reports.export.sale-payments-pdf', request()->query()) }}" target="_blank"
                class="btn btn-primary btn-wave waves-effect waves-light">
                <i class="ri-upload-2-line me-2"></i> {{ __('Export PDF') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    @include('tenants.reports.partials.filters', [
        'actionRouteName' => 'tenant.reports.sale-payments',
        'enabledFilters' => ['date_range', 'payment_method', 'customer', 'search'],
    ])

    <div class="card custom-card border-0 shadow-sm h-100 mt-3">
        <div class="card-header">
            <h6 class="mb-0">{{ __('Sale Payments Report') }}</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Invoice') }}</th>
                            <th>{{ __('Method') }}</th>
                            <th>{{ __('Paid At') }}</th>
                            <th>{{ __('Reference') }}</th>
                            <th class="text-end">{{ __('Amount') }}</th>
                        </tr>
                    </thead>
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
                            <tr>
                                <td colspan="5" class="text-center text-muted">{{ __('No sale payments found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $salePayments->links() }}</div>
        </div>
    </div>
@endsection

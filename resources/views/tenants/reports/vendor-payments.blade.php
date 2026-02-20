@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [['label' => __('Reports')], ['label' => __('Vendor Payments')]];
    @endphp

    <x-breadcrumb title="{{ __('Vendor Payments Report') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.reports.export.vendor-payments-pdf', request()->query()) }}" target="_blank"
                class="btn btn-primary btn-wave waves-effect waves-light">
                <i class="ri-upload-2-line me-2"></i> {{ __('Export PDF') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    @include('tenants.reports.partials.filters', [
        'actionRouteName' => 'tenant.reports.vendor-payments',
        'enabledFilters' => ['date_range', 'payment_method', 'vendor', 'search'],
    ])

    <div class="card custom-card border-0 shadow-sm h-100 mt-3">
        <div class="card-header">
            <h6 class="mb-0">{{ __('Vendor Payments Report') }}</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Payment No') }}</th>
                            <th>{{ __('Vendor') }}</th>
                            <th>{{ __('Method') }}</th>
                            <th>{{ __('Paid At') }}</th>
                            <th class="text-end">{{ __('Amount') }}</th>
                        </tr>
                    </thead>
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
                            <tr>
                                <td colspan="5" class="text-center text-muted">{{ __('No vendor payments found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $vendorPayments->links() }}</div>
        </div>
    </div>
@endsection

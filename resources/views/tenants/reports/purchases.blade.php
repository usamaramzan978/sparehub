@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [['label' => __('Reports')], ['label' => __('Purchases')]];
    @endphp

    <x-breadcrumb title="{{ __('Purchases Report') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.reports.export.purchases-pdf', request()->query()) }}" target="_blank"
                class="btn btn-primary btn-wave waves-effect waves-light">
                <i class="ri-upload-2-line me-2"></i> {{ __('Export PDF') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    @include('tenants.reports.partials.filters', [
        'actionRouteName' => 'tenant.reports.purchases',
        'enabledFilters' => ['date_range', 'purchase_status', 'vendor', 'min_total', 'max_total', 'search'],
    ])

    <div class="card custom-card border-0 shadow-sm h-100 mt-3">
        <div class="card-header">
            <h6 class="mb-0">{{ __('Purchase Invoices Report') }}</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Purchase') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Vendor') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="text-end">{{ __('Grand Total') }}</th>
                            <th class="text-end">{{ __('Balance') }}</th>
                        </tr>
                    </thead>
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
                            <tr>
                                <td colspan="6" class="text-center text-muted">{{ __('No purchases found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $purchases->links() }}</div>
        </div>
    </div>
@endsection

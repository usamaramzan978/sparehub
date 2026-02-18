@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [['label' => __('Sales')], ['label' => __('Sale Payments')]];
    @endphp

    <x-breadcrumb title="{{ __('Sale Payments') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.sale-payments.create') }}" class="btn btn-primary">{{ __('Add Payment') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Invoice') }}</th>
                            <th>{{ __('Method') }}</th>
                            <th>{{ __('Amount') }}</th>
                            <th>{{ __('Received By') }}</th>
                            <th>{{ __('Paid At') }}</th>
                            <th>{{ __('Reference') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $payment)
                            <tr>
                                <td>{{ $payment->sale?->invoice_no ?? '-' }}</td>
                                <td>{{ ucfirst($payment->payment_method->value) }}</td>
                                <td>{{ number_format((float) $payment->amount, 2) }}</td>
                                <td>{{ $payment->receiver?->name ?? '-' }}</td>
                                <td>{{ $payment->paid_at?->format('Y-m-d H:i') }}</td>
                                <td>{{ $payment->reference_no ?? '-' }}</td>
                                <td class="text-end">
                                    <div class="btn-list">
                                        <a href="{{ route('tenant.sale-payments.show', $payment) }}"
                                            class="btn btn-sm btn-icon btn-primary-light btn-wave waves-effect waves-light"
                                            title="{{ __('View') }}">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                        <a href="{{ route('tenant.sale-payments.edit', $payment) }}"
                                            class="btn btn-sm btn-icon btn-secondary-light btn-wave waves-effect waves-light">
                                            <i class="ri-edit-line"></i>
                                        </a>
                                        <button type="button"
                                            class="btn btn-sm btn-icon btn-danger-light btn-wave waves-effect waves-light js-delete-modal"
                                            data-action="{{ route('tenant.sale-payments.destroy', $payment) }}"
                                            data-name="{{ $payment->sale?->invoice_no ?? '-' }}"
                                            data-title="{{ __('Delete Sale Payment') }}"
                                            data-message="{{ __('Are you sure you want to delete this payment?') }}"
                                            data-bs-toggle="modal" data-bs-target="#salePaymentDeleteModal">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">{{ __('No sale payments found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $items->links() }}
            </div>
        </div>
    </div>

    <x-delete-modal id="salePaymentDeleteModal" />
@endsection

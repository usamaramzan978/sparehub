@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [['label' => __('Purchases')], ['label' => __('Vendor Payments')]];
    @endphp

    <x-breadcrumb title="{{ __('Vendor Payments') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.vendor-payments.create') }}" class="btn btn-primary">{{ __('Add Payment') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Payment No') }}</th>
                            <th>{{ __('Vendor') }}</th>
                            <th>{{ __('Purchase') }}</th>
                            <th>{{ __('Method') }}</th>
                            <th>{{ __('Amount') }}</th>
                            <th>{{ __('Paid At') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $payment)
                            <tr>
                                <td>{{ $payment->payment_no }}</td>
                                <td>{{ $payment->vendor?->name ?? '-' }}</td>
                                <td>{{ $payment->purchase?->purchase_no ?? '-' }}</td>
                                <td>{{ ucfirst($payment->payment_method->value) }}</td>
                                <td>{{ number_format((float) $payment->amount, 2) }}</td>
                                <td>@tenantDate($payment->paid_at, 'Y-m-d H:i', '')</td>
                                <td class="text-end">
                                    <div class="btn-list">
                                        <a href="{{ route('tenant.vendor-payments.show', $payment) }}"
                                            class="btn btn-sm btn-icon btn-primary-light btn-wave waves-effect waves-light"
                                            title="{{ __('View') }}">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                        <a href="{{ route('tenant.vendor-payments.edit', $payment) }}"
                                            class="btn btn-sm btn-icon btn-secondary-light btn-wave waves-effect waves-light">
                                            <i class="ri-edit-line"></i>
                                        </a>
                                        <button type="button"
                                            class="btn btn-sm btn-icon btn-danger-light btn-wave waves-effect waves-light js-delete-modal"
                                            data-action="{{ route('tenant.vendor-payments.destroy', $payment) }}"
                                            data-name="{{ $payment->payment_no }}"
                                            data-title="{{ __('Delete Vendor Payment') }}"
                                            data-message="{{ __('Are you sure you want to delete this payment?') }}"
                                            data-bs-toggle="modal" data-bs-target="#vendorPaymentDeleteModal">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">{{ __('No vendor payments found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $items->links() }}</div>
        </div>
    </div>

    <x-delete-modal id="vendorPaymentDeleteModal" />
@endsection

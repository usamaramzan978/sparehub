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

    <div class="card custom-card border-0 shadow-sm h-100" data-ajax-table-search
        data-form-selector="#sale-payments-search-form" data-input-selector="#sale-payments-search"
        data-table-body-selector="#sale-payments-table tbody"
        data-pagination-selector="[data-sale-payments-pagination]" data-loading-selector="#sale-payments-search-loading"
        data-search-param="search" data-debounce="350" data-min-loading-visible="220">
        <div class="card-header d-flex justify-content-between align-items-end flex-wrap gap-3">
            <div class="card-title mb-0">
                {{ __('Sale Payments') }}
            </div>
            <form method="GET" action="{{ route('tenant.sale-payments.index') }}"
                class="d-flex align-items-end gap-2 flex-wrap" id="sale-payments-search-form">
                <div class="position-relative">
                    <input type="text" name="search" id="sale-payments-search" class="form-control pe-5"
                        value="{{ request('search') }}"
                        placeholder="{{ __('Search by invoice, method, reference, or receiver') }}">
                    <span id="sale-payments-search-loading"
                        class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted opacity-0 pe-none"
                        style="transition: opacity 0.2s ease;" aria-hidden="true">
                        <span class="spinner-border spinner-border-sm"></span>
                    </span>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0" id="sale-payments-table">
                    <thead>
                        <tr>
                            <th>{{ __('Invoice') }}</th>
                            <x-sortable-column :label="__('Method')" column="payment_method" :current-sort-by="$sortBy"
                                :current-sort-direction="$sortDirection" />
                            <x-sortable-column :label="__('Amount')" column="amount" :current-sort-by="$sortBy"
                                :current-sort-direction="$sortDirection" />
                            <th>{{ __('Received By') }}</th>
                            <x-sortable-column :label="__('Paid At')" column="paid_at" :current-sort-by="$sortBy"
                                :current-sort-direction="$sortDirection" />
                            <x-sortable-column :label="__('Reference')" column="reference_no" :current-sort-by="$sortBy"
                                :current-sort-direction="$sortDirection" />
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
                                <td>@tenantDate($payment->paid_at, 'Y-m-d H:i', '')</td>
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

            <div class="mt-3" data-sale-payments-pagination>
                {{ $items->links() }}
            </div>
        </div>
    </div>

    <x-delete-modal id="salePaymentDeleteModal" />
@endsection

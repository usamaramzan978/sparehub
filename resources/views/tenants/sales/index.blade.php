@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [['label' => __('Sales')], ['label' => __('Sale Invoices')]];
    @endphp

    <x-breadcrumb title="{{ __('Sale Invoices') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.sales.create') }}" class="btn btn-primary">{{ __('Add Invoice') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card custom-card border-0 shadow-sm h-100"
        @include('components.ajax-table-attributes', ['formSelector' => '#sales-search-form', 'inputSelector' => '#sales-search', 'tableBodySelector' => '#sales-table tbody', 'paginationSelector' => '[data-sales-pagination]', 'loadingSelector' => '#sales-search-loading', 'searchParam' => 'search', 'debounce' => '350', 'minLoadingVisible' => '220'])>
        <div class="card-header d-flex justify-content-between align-items-end flex-wrap gap-3">
            <div class="card-title mb-0">
                {{ __('Sale Invoices') }}
            </div>
            <form method="GET" action="{{ route('tenant.sales.index') }}" class="d-flex align-items-end gap-2 flex-wrap"
                id="sales-search-form">
                <div class="position-relative">
                    <input type="text" name="search" id="sales-search" class="form-control pe-5"
                        value="{{ request('search') }}" placeholder="{{ __('Search by invoice no or customer') }}">
                    <span id="sales-search-loading"
                        class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted opacity-0 pe-none"
                        style="transition: opacity 0.2s ease;" aria-hidden="true">
                        <span class="spinner-border spinner-border-sm"></span>
                    </span>
                </div>
                <div>
                    <label class="form-label mb-1" for="sales-payment-status">{{ __('Payment') }}</label>
                    <select name="payment_status" id="sales-payment-status" class="form-select">
                        <option value="">{{ __('All') }}</option>
                        <option value="paid" @selected(request('payment_status') === 'paid')>{{ __('Paid') }}</option>
                        <option value="partial" @selected(request('payment_status') === 'partial')>{{ __('Partial') }}</option>
                        <option value="unpaid" @selected(request('payment_status') === 'unpaid')>{{ __('Unpaid') }}</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0" id="sales-table">
                    <thead>
                        <tr>
                            <x-sortable-column :label="__('Invoice No')" column="invoice_no" :current-sort-by="$sortBy"
                                :current-sort-direction="$sortDirection" />
                            <x-sortable-column :label="__('Date')" column="invoice_date" :current-sort-by="$sortBy"
                                :current-sort-direction="$sortDirection" />
                            <th>{{ __('Customer') }}</th>
                            <x-sortable-column :label="__('Type')" column="invoice_type" :current-sort-by="$sortBy"
                                :current-sort-direction="$sortDirection" />
                            <x-sortable-column :label="__('Status')" column="status" :current-sort-by="$sortBy"
                                :current-sort-direction="$sortDirection" />
                            <th>{{ __('Payment') }}</th>
                            <x-sortable-column :label="__('Grand Total')" column="grand_total" :current-sort-by="$sortBy"
                                :current-sort-direction="$sortDirection" />
                            <x-sortable-column :label="__('Balance')" column="balance_due" :current-sort-by="$sortBy"
                                :current-sort-direction="$sortDirection" />
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $sale)
                            @php
                                $statusValue = $sale->status->value;
                                $statusClass = match ($statusValue) {
                                    'posted', 'completed' => 'bg-success',
                                    'hold' => 'bg-warning',
                                    'cancelled' => 'bg-danger',
                                    'returned' => 'bg-info',
                                    default => 'bg-secondary',
                                };
                                $invoiceTypeValue = $sale->invoice_type->value;
                                $invoiceTypeBadgeClass = match ($invoiceTypeValue) {
                                    'product' => 'badge bg-primary-transparent',
                                    'service' => 'badge bg-info-transparent',
                                    'mixed' => 'badge bg-dark-transparent',
                                    default => 'border border-secondary text-secondary',
                                };
                                $balanceDue = (float) $sale->balance_due;
                                $paidTotal = (float) $sale->paid_total;
                                if ($statusValue === 'hold') {
                                    $paymentStatusLabel = __('Not Paid');
                                    $paymentStatusClass = 'bg-danger-transparent text-danger';
                                } elseif ($balanceDue <= 0) {
                                    $paymentStatusLabel = __('Paid');
                                    $paymentStatusClass = 'bg-success-transparent text-success';
                                } elseif ($paidTotal > 0) {
                                    $paymentStatusLabel = __('Partial');
                                    $paymentStatusClass = 'bg-warning-transparent text-warning';
                                } else {
                                    $paymentStatusLabel = __('Unpaid');
                                    $paymentStatusClass = 'bg-danger-transparent text-danger';
                                }
                            @endphp
                            <tr>
                                <td>{{ $sale->invoice_no }}</td>
                                <td>@tenantDate($sale->invoice_date, 'Y-m-d', '')</td>
                                <td>{{ $sale->customer?->name ?? '-' }}</td>
                                <td>
                                    <span
                                        class="badge {{ $invoiceTypeBadgeClass }}">{{ ucfirst($invoiceTypeValue) }}</span>
                                </td>
                                <td>
                                    <span
                                        class="badge {{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $statusValue)) }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $paymentStatusClass }}">{{ $paymentStatusLabel }}</span>
                                </td>
                                <td>{{ number_format((float) $sale->grand_total, 2) }}</td>
                                <td>{{ number_format((float) $sale->balance_due, 2) }}</td>
                                <td class="text-end">
                                    <div class="btn-list">
                                        <a href="{{ route('tenant.sales.show', $sale) }}"
                                            class="btn btn-sm btn-icon btn-primary-light btn-wave waves-effect waves-light"
                                            title="{{ __('View') }}">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                        <a href="{{ route('tenant.sales.edit', $sale) }}"
                                            class="btn btn-sm btn-icon btn-secondary-light btn-wave waves-effect waves-light">
                                            <i class="ri-edit-line"></i>
                                        </a>
                                        <button type="button"
                                            class="btn btn-sm btn-icon btn-danger-light btn-wave waves-effect waves-light js-delete-modal"
                                            data-action="{{ route('tenant.sales.destroy', $sale) }}"
                                            data-name="{{ $sale->invoice_no }}" data-title="{{ __('Delete Invoice') }}"
                                            data-message="{{ __('Are you sure you want to delete this invoice?') }}"
                                            data-bs-toggle="modal" data-bs-target="#saleDeleteModal">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">{{ __('No invoices found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3" data-sales-pagination>{{ $items->links() }}</div>
        </div>
    </div>

    <x-delete-modal id="saleDeleteModal" />
@endsection

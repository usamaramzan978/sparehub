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

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-body">
            <form method="GET" action="{{ route('tenant.sales.index') }}" class="row g-2 mb-3">
                <div class="col-md-10">
                    <label class="form-label" for="search">{{ __('Search') }}</label>
                    <input type="text" class="form-control" id="search" name="search" value="{{ request('search') }}"
                        placeholder="{{ __('Invoice no or customer') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button class="btn btn-primary w-100" type="submit">{{ __('Filter') }}</button>
                    <a href="{{ route('tenant.sales.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Invoice No') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Grand Total') }}</th>
                            <th>{{ __('Balance') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $sale)
                            <tr>
                                <td>{{ $sale->invoice_no }}</td>
                                <td>{{ $sale->invoice_date?->format('Y-m-d') }}</td>
                                <td>{{ $sale->customer?->name ?? '-' }}</td>
                                <td>{{ ucfirst($sale->invoice_type->value) }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $sale->status->value)) }}</td>
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
                                <td colspan="8" class="text-center text-muted">{{ __('No invoices found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $items->links() }}</div>
        </div>
    </div>

    <x-delete-modal id="saleDeleteModal" />
@endsection

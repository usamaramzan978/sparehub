@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [['label' => __('Purchases')], ['label' => __('Purchase Orders')]];
    @endphp

    <x-breadcrumb title="{{ __('Purchase Orders') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.purchases.create') }}" class="btn btn-primary">{{ __('Add Purchase') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-body">
            <form method="GET" action="{{ route('tenant.purchases.index') }}" class="row g-2 mb-3">
                <div class="col-md-10">
                    <label class="form-label" for="search">{{ __('Search') }}</label>
                    <input type="text" class="form-control" id="search" name="search" value="{{ request('search') }}"
                        placeholder="{{ __('Purchase no, vendor invoice or vendor') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button class="btn btn-primary w-100" type="submit">{{ __('Filter') }}</button>
                    <a href="{{ route('tenant.purchases.index') }}"
                        class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Purchase No') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Vendor') }}</th>
                            <th>{{ __('Warehouse') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Grand Total') }}</th>
                            <th>{{ __('Balance') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $purchase)
                            <tr>
                                <td>{{ $purchase->purchase_no }}</td>
                                <td>@tenantDate($purchase->purchase_date, 'Y-m-d', '')</td>
                                <td>{{ $purchase->vendor?->name ?? '-' }}</td>
                                <td>{{ $purchase->warehouse?->name ?? '-' }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $purchase->status->value)) }}</td>
                                <td>{{ number_format((float) $purchase->grand_total, 2) }}</td>
                                <td>{{ number_format((float) $purchase->balance_due, 2) }}</td>
                                <td class="text-end">
                                    <div class="btn-list">
                                        <a href="{{ route('tenant.purchases.show', $purchase) }}"
                                            class="btn btn-sm btn-icon btn-primary-light btn-wave waves-effect waves-light"
                                            title="{{ __('View') }}">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                        <a href="{{ route('tenant.purchases.edit', $purchase) }}"
                                            class="btn btn-sm btn-icon btn-secondary-light btn-wave waves-effect waves-light">
                                            <i class="ri-edit-line"></i>
                                        </a>
                                        <button type="button"
                                            class="btn btn-sm btn-icon btn-danger-light btn-wave waves-effect waves-light js-delete-modal"
                                            data-action="{{ route('tenant.purchases.destroy', $purchase) }}"
                                            data-name="{{ $purchase->purchase_no }}"
                                            data-title="{{ __('Delete Purchase') }}"
                                            data-message="{{ __('Are you sure you want to delete this purchase?') }}"
                                            data-bs-toggle="modal" data-bs-target="#purchaseDeleteModal">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">{{ __('No purchases found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $items->links() }}</div>
        </div>
    </div>

    <x-delete-modal id="purchaseDeleteModal" />
@endsection

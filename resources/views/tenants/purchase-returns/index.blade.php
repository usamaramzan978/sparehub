@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [['label' => __('Purchases')], ['label' => __('Purchase Returns')]];
    @endphp

    <x-breadcrumb title="{{ __('Purchase Returns') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.purchase-returns.create') }}" class="btn btn-primary">{{ __('Add Return') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-body">
            <form method="GET" action="{{ route('tenant.purchase-returns.index') }}" class="row g-2 mb-3">
                <div class="col-md-10">
                    <label class="form-label" for="search">{{ __('Search') }}</label>
                    <input type="text" class="form-control" id="search" name="search" value="{{ request('search') }}"
                        placeholder="{{ __('Return no or vendor') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button class="btn btn-primary w-100" type="submit">{{ __('Filter') }}</button>
                    <a href="{{ route('tenant.purchase-returns.index') }}"
                        class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Return No') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Vendor') }}</th>
                            <th>{{ __('Purchase') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Grand Total') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $return)
                            <tr>
                                <td>{{ $return->return_no }}</td>
                                <td>@tenantDate($return->return_date, 'Y-m-d', '')</td>
                                <td>{{ $return->vendor?->name ?? '-' }}</td>
                                <td>{{ $return->purchase?->purchase_no ?? '-' }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $return->status->value)) }}</td>
                                <td>{{ number_format((float) $return->grand_total, 2) }}</td>
                                <td class="text-end">
                                    <div class="btn-list">
                                        <a href="{{ route('tenant.purchase-returns.show', $return) }}"
                                            class="btn btn-sm btn-icon btn-primary-light btn-wave waves-effect waves-light"
                                            title="{{ __('View') }}">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                        <a href="{{ route('tenant.purchase-returns.edit', $return) }}"
                                            class="btn btn-sm btn-icon btn-secondary-light btn-wave waves-effect waves-light">
                                            <i class="ri-edit-line"></i>
                                        </a>
                                        <button type="button"
                                            class="btn btn-sm btn-icon btn-danger-light btn-wave waves-effect waves-light js-delete-modal"
                                            data-action="{{ route('tenant.purchase-returns.destroy', $return) }}"
                                            data-name="{{ $return->return_no }}"
                                            data-title="{{ __('Delete Purchase Return') }}"
                                            data-message="{{ __('Are you sure you want to delete this return?') }}"
                                            data-bs-toggle="modal" data-bs-target="#purchaseReturnDeleteModal">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">{{ __('No purchase returns found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $items->links() }}</div>
        </div>
    </div>

    <x-delete-modal id="purchaseReturnDeleteModal" />
@endsection

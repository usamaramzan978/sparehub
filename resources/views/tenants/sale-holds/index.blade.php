@extends('layouts.app')

@section('content')
    @include('tenants.sale-holds.partials.create', ['customers' => $customers])

    @php
        $breadcrumbs = [['label' => __('Sales')], ['label' => __('Sale Holds (POS Hold)')]];
    @endphp

    <x-breadcrumb title="{{ __('Sale Holds (POS Hold)') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#saleHoldCreateModal">
                {{ __('Add Hold') }}
            </button>
        </x-slot:actions>
    </x-breadcrumb>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('tenant.sale-holds.index') }}" class="row g-2 mb-3">
                <div class="col-md-10">
                    <label class="form-label" for="search">{{ __('Search') }}</label>
                    <input type="text" class="form-control" id="search" name="search" value="{{ request('search') }}"
                        placeholder="{{ __('Hold no or customer') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button class="btn btn-primary w-100" type="submit">{{ __('Filter') }}</button>
                    <a href="{{ route('tenant.sale-holds.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Hold No') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Expires At') }}</th>
                            <th>{{ __('Created') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $hold)
                            <tr>
                                <td>{{ $hold->hold_no }}</td>
                                <td>{{ $hold->customer?->name ?? '-' }}</td>
                                <td>{{ $hold->expires_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                <td>{{ $hold->created_at?->format('Y-m-d H:i') }}</td>
                                <td class="text-end">
                                    <div class="btn-list">
                                        <a href="{{ route('tenant.sale-holds.show', $hold) }}"
                                            class="btn btn-sm btn-icon btn-primary-light btn-wave waves-effect waves-light"
                                            title="{{ __('View') }}">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                        <button type="button"
                                            class="btn btn-sm btn-icon btn-secondary-light btn-wave waves-effect waves-light"
                                            data-bs-toggle="modal" data-bs-target="#saleHoldEditModal-{{ $hold->id }}">
                                            <i class="ri-edit-line"></i>
                                        </button>
                                        <button type="button"
                                            class="btn btn-sm btn-icon btn-danger-light btn-wave waves-effect waves-light js-delete-modal"
                                            data-action="{{ route('tenant.sale-holds.destroy', $hold) }}"
                                            data-name="{{ $hold->hold_no }}" data-title="{{ __('Delete Sale Hold') }}"
                                            data-message="{{ __('Are you sure you want to delete this hold?') }}"
                                            data-bs-toggle="modal" data-bs-target="#saleHoldDeleteModal">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @include('tenants.sale-holds.partials.edit', [
                                'saleHold' => $hold,
                                'customers' => $customers,
                            ])
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">{{ __('No sale holds found.') }}</td>
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

    <x-delete-modal id="saleHoldDeleteModal" />
@endsection

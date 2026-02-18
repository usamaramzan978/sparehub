@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [['label' => __('People')], ['label' => __('Customer Vehicles')]];
    @endphp

    <x-breadcrumb title="{{ __('Customer Vehicles') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.customer-vehicles.create') }}" class="btn btn-primary">{{ __('Add Vehicle') }}</a>
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

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-body">
            <form method="GET" action="{{ route('tenant.customer-vehicles.index') }}" class="row g-2 mb-3">
                <div class="col-md-10">
                    <label class="form-label" for="search">{{ __('Search') }}</label>
                    <input type="text" name="search" id="search" class="form-control" value="{{ request('search') }}"
                        placeholder="{{ __('Registration, model, chassis') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100">{{ __('Filter') }}</button>
                    <a href="{{ route('tenant.customer-vehicles.index') }}"
                        class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Registration') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Model') }}</th>
                            <th>{{ __('Year') }}</th>
                            <th>{{ __('Meter Reading') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $vehicle)
                            <tr>
                                <td>{{ $vehicle->registration_no }}</td>
                                <td>{{ $vehicle->customer?->name ?? '-' }}</td>
                                <td>{{ $vehicle->model ?: '-' }}</td>
                                <td>{{ $vehicle->year ?: '-' }}</td>
                                <td>{{ number_format((float) $vehicle->meter_reading, 3) }}</td>
                                <td class="text-end">
                                    <div class="btn-list">
                                        <a href="{{ route('tenant.customer-vehicles.show', $vehicle) }}"
                                            class="btn btn-sm btn-icon btn-primary-light btn-wave waves-effect waves-light"
                                            title="{{ __('View') }}">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                        <a href="{{ route('tenant.customer-vehicles.edit', $vehicle) }}"
                                            class="btn btn-sm btn-icon btn-secondary-light btn-wave waves-effect waves-light"
                                            title="{{ __('Edit') }}">
                                            <i class="ri-edit-line"></i>
                                        </a>
                                        <button type="button"
                                            class="btn btn-sm btn-icon btn-danger-light btn-wave waves-effect waves-light js-delete-modal"
                                            data-action="{{ route('tenant.customer-vehicles.destroy', $vehicle) }}"
                                            data-name="{{ $vehicle->registration_no }}"
                                            data-title="{{ __('Delete Vehicle') }}"
                                            data-message="{{ __('Are you sure you want to delete this vehicle?') }}"
                                            data-bs-toggle="modal" data-bs-target="#customerVehicleDeleteModal"
                                            title="{{ __('Delete') }}">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">{{ __('No vehicles found.') }}</td>
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

    <x-delete-modal id="customerVehicleDeleteModal" />
@endsection

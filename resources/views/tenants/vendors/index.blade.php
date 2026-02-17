@extends('layouts.app')

@section('content')
    @include('tenants.vendors.partials.create', ['statuses' => $statuses])

    @php
        $breadcrumbs = [
            ['label' => __('People')],
            ['label' => __('Vendors')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Vendors') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#vendorCreateModal">
                {{ __('Add Vendor') }}
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
            <form method="GET" action="{{ route('tenant.vendors.index') }}" class="row g-2 mb-3">
                <div class="col-md-10">
                    <label class="form-label" for="search">{{ __('Search') }}</label>
                    <input type="text" name="search" id="search" class="form-control" value="{{ request('search') }}"
                        placeholder="{{ __('Code, name, phone') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100">{{ __('Filter') }}</button>
                    <a href="{{ route('tenant.vendors.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Phone') }}</th>
                            <th>{{ __('City') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $vendor)
                            <tr>
                                <td>{{ $vendor->code }}</td>
                                <td>{{ $vendor->name }}</td>
                                <td>{{ $vendor->phone ?: '-' }}</td>
                                <td>{{ $vendor->city ?: '-' }}</td>
                                <td>
                                    @if ($vendor->status->value === 'active')
                                        <span class="badge bg-success-transparent">{{ __('Active') }}</span>
                                    @else
                                        <span class="badge bg-secondary-transparent">{{ __('Inactive') }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-list">
                                        <a href="{{ route('tenant.vendors.show', $vendor) }}"
                                            class="btn btn-sm btn-icon btn-primary-light btn-wave waves-effect waves-light"
                                            title="{{ __('View') }}">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                        <button type="button"
                                            class="btn btn-sm btn-icon btn-secondary-light btn-wave waves-effect waves-light"
                                            data-bs-toggle="modal" data-bs-target="#vendorEditModal-{{ $vendor->id }}"
                                            title="{{ __('Edit') }}">
                                            <i class="ri-edit-line"></i>
                                        </button>
                                        <button type="button"
                                            class="btn btn-sm btn-icon btn-danger-light btn-wave waves-effect waves-light js-delete-modal"
                                            data-action="{{ route('tenant.vendors.destroy', $vendor) }}"
                                            data-name="{{ $vendor->name }}" data-title="{{ __('Delete Vendor') }}"
                                            data-message="{{ __('Are you sure you want to delete this vendor?') }}"
                                            data-bs-toggle="modal" data-bs-target="#vendorDeleteModal"
                                            title="{{ __('Delete') }}">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @include('tenants.vendors.partials.edit', ['vendor' => $vendor, 'statuses' => $statuses])
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">{{ __('No vendors found.') }}</td>
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

    <x-delete-modal id="vendorDeleteModal" />
@endsection

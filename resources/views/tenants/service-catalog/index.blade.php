@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [['label' => __('Catalog')], ['label' => __('Service Catalog')]];
    @endphp

    <x-breadcrumb title="{{ __('Service Catalog') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.service-catalog.create') }}" class="btn btn-primary">{{ __('Add Service') }}</a>
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
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Category') }}</th>
                            <th>{{ __('Base Price') }}</th>
                            <th>{{ __('Duration (Min)') }}</th>
                            <th>{{ __('Taxable') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $serviceCatalog)
                            <tr>
                                <td>{{ $serviceCatalog->code }}</td>
                                <td>{{ $serviceCatalog->name }}</td>
                                <td>{{ $serviceCatalog->category ?: '-' }}</td>
                                <td>{{ number_format((float) $serviceCatalog->base_price, 2) }}</td>
                                <td>{{ $serviceCatalog->duration_minutes ?: '-' }}</td>
                                <td>
                                    @if ($serviceCatalog->status->value === 'active')
                                        <span class="badge bg-success-transparent">{{ __('Active') }}</span>
                                    @else
                                        <span class="badge bg-secondary-transparent">{{ __('Inactive') }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-list">
                                        <span class="d-inline-block" data-bs-toggle="tooltip" title="{{ __('View') }}">
                                            <a href="{{ route('tenant.service-catalog.show', $serviceCatalog) }}"
                                                class="btn btn-sm btn-icon btn-primary-light btn-wave waves-effect waves-light">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                        </span>
                                        <span class="d-inline-block" data-bs-toggle="tooltip" title="{{ __('Edit') }}">
                                            <a href="{{ route('tenant.service-catalog.edit', $serviceCatalog) }}"
                                                class="btn btn-sm btn-icon btn-secondary-light btn-wave waves-effect waves-light"
                                                title="{{ __('Edit') }}">
                                                <i class="ri-edit-line"></i>
                                            </a>
                                        </span>
                                        <span class="d-inline-block" data-bs-toggle="tooltip" title="{{ __('Delete') }}">
                                            <button type="button"
                                                class="btn btn-sm btn-icon btn-danger-light btn-wave waves-effect waves-light js-delete-modal"
                                                data-action="{{ route('tenant.service-catalog.destroy', $serviceCatalog) }}"
                                                data-name="{{ $serviceCatalog->name }}"
                                                data-title="{{ __('Delete Service') }}"
                                                data-message="{{ __('Are you sure you want to delete this service?') }}"
                                                data-bs-toggle="modal" data-bs-target="#serviceCatalogDeleteModal">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">{{ __('No services found.') }}</td>
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

    <x-delete-modal id="serviceCatalogDeleteModal" />
@endsection

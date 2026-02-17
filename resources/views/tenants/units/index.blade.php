@extends('layouts.app')

@section('content')
    @include('tenants.units.partials.create', ['statuses' => $statuses])

    @php
        $breadcrumbs = [
            ['label' => __('Catalog')],
            ['label' => __('Units')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Units') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#unitCreateModal">
                {{ __('Add Unit') }}
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
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Fractional') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $unit)
                            <tr>
                                <td>{{ $unit->code }}</td>
                                <td>{{ $unit->name }}</td>
                                <td>{{ $unit->is_fractional ? __('Yes') : __('No') }}</td>
                                <td>
                                    @if ($unit->status->value === 'active')
                                        <span class="badge bg-success-transparent">{{ __('Active') }}</span>
                                    @else
                                        <span class="badge bg-secondary-transparent">{{ __('Inactive') }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-list">
                                        <span class="d-inline-block" data-bs-toggle="tooltip" title="{{ __('View') }}">
                                            <button type="button"
                                                class="btn btn-sm btn-icon btn-primary-light btn-wave waves-effect waves-light js-view-unit"
                                                data-code="{{ $unit->code }}"
                                                data-name="{{ $unit->name }}"
                                                data-fractional="{{ $unit->is_fractional ? __('Yes') : __('No') }}"
                                                data-status="{{ $unit->status->value }}" data-bs-toggle="modal"
                                                data-bs-target="#unitViewModal">
                                                <i class="ri-eye-line"></i>
                                            </button>
                                        </span>
                                        <span class="d-inline-block" data-bs-toggle="tooltip" title="{{ __('Edit') }}">
                                            <button type="button"
                                                class="btn btn-sm btn-icon btn-secondary-light btn-wave waves-effect waves-light"
                                                data-bs-toggle="modal" data-bs-target="#unitEditModal-{{ $unit->id }}">
                                                <i class="ri-edit-line"></i>
                                            </button>
                                        </span>
                                        <span class="d-inline-block" data-bs-toggle="tooltip" title="{{ __('Delete') }}">
                                            <button type="button"
                                                class="btn btn-sm btn-icon btn-danger-light btn-wave waves-effect waves-light js-delete-modal"
                                                data-action="{{ route('tenant.units.destroy', $unit) }}"
                                                data-name="{{ $unit->name }}"
                                                data-title="{{ __('Delete Unit') }}"
                                                data-message="{{ __('Are you sure you want to delete this unit?') }}"
                                                data-bs-toggle="modal" data-bs-target="#unitDeleteModal">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            @include('tenants.units.partials.edit', ['unit' => $unit, 'statuses' => $statuses])
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">{{ __('No units found.') }}</td>
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

    <div class="modal fade" id="unitViewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Unit Details') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3">{{ __('Code') }}</dt>
                        <dd class="col-sm-9" id="unit-view-code">-</dd>
                        <dt class="col-sm-3">{{ __('Name') }}</dt>
                        <dd class="col-sm-9" id="unit-view-name">-</dd>
                        <dt class="col-sm-3">{{ __('Fractional') }}</dt>
                        <dd class="col-sm-9" id="unit-view-fractional">-</dd>
                        <dt class="col-sm-3">{{ __('Status') }}</dt>
                        <dd class="col-sm-9" id="unit-view-status">-</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <x-delete-modal id="unitDeleteModal" />

    @push('scripts')
        <script>
            document.querySelectorAll('.js-view-unit').forEach((button) => {
                button.addEventListener('click', () => {
                    document.getElementById('unit-view-code').textContent = button.dataset.code || '-';
                    document.getElementById('unit-view-name').textContent = button.dataset.name || '-';
                    document.getElementById('unit-view-fractional').textContent = button.dataset.fractional || '-';
                    document.getElementById('unit-view-status').textContent = button.dataset.status || '-';
                });
            });
        </script>
    @endpush
@endsection

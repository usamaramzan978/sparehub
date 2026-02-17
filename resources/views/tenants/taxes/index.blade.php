@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Finance')],
            ['label' => __('Taxes')],
        ];
    @endphp

    @include('tenants.taxes.partials.create', ['statuses' => $statuses])

    <x-breadcrumb title="{{ __('Taxes') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#taxCreateModal">
                {{ __('Add Tax') }}
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
                            <th>{{ __('Rate (%)') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $tax)
                            <tr>
                                <td>{{ $tax->code }}</td>
                                <td>{{ $tax->name }}</td>
                                <td>{{ $tax->rate }}</td>
                                <td>
                                    @if ($tax->status->value === 'active')
                                        <span class="badge bg-success-transparent">{{ __('Active') }}</span>
                                    @else
                                        <span class="badge bg-secondary-transparent">{{ __('Inactive') }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-list">
                                        <span class="d-inline-block" data-bs-toggle="tooltip" title="{{ __('View') }}">
                                            <button type="button"
                                                class="btn btn-sm btn-icon btn-primary-light btn-wave waves-effect waves-light js-view-tax"
                                                data-code="{{ $tax->code }}"
                                                data-name="{{ $tax->name }}"
                                                data-rate="{{ $tax->rate }}"
                                                data-inclusive="{{ $tax->is_inclusive ? __('Yes') : __('No') }}"
                                                data-status="{{ $tax->status->value }}"
                                                data-bs-toggle="modal" data-bs-target="#taxViewModal">
                                                <i class="ri-eye-line"></i>
                                            </button>
                                        </span>
                                        <span class="d-inline-block" data-bs-toggle="tooltip" title="{{ __('Edit') }}">
                                            <button type="button"
                                                class="btn btn-sm btn-icon btn-secondary-light btn-wave waves-effect waves-light"
                                                data-bs-toggle="modal" data-bs-target="#taxEditModal-{{ $tax->id }}">
                                                <i class="ri-edit-line"></i>
                                            </button>
                                        </span>
                                        <span class="d-inline-block" data-bs-toggle="tooltip" title="{{ __('Delete') }}">
                                            <button type="button"
                                                class="btn btn-sm btn-icon btn-danger-light btn-wave waves-effect waves-light js-delete-modal"
                                                data-action="{{ route('tenant.taxes.destroy', $tax) }}"
                                                data-name="{{ $tax->code }}"
                                                data-title="{{ __('Delete Tax') }}"
                                                data-message="{{ __('Are you sure you want to delete this tax?') }}"
                                                data-bs-toggle="modal" data-bs-target="#taxDeleteModal">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            @include('tenants.taxes.partials.edit', ['tax' => $tax, 'statuses' => $statuses])
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">{{ __('No taxes found.') }}</td>
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

    <div class="modal fade" id="taxViewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Tax Details') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3">{{ __('Code') }}</dt>
                        <dd class="col-sm-9" id="tax-view-code">-</dd>
                        <dt class="col-sm-3">{{ __('Name') }}</dt>
                        <dd class="col-sm-9" id="tax-view-name">-</dd>
                        <dt class="col-sm-3">{{ __('Rate') }}</dt>
                        <dd class="col-sm-9" id="tax-view-rate">-</dd>
                        <dt class="col-sm-3">{{ __('Inclusive') }}</dt>
                        <dd class="col-sm-9" id="tax-view-inclusive">-</dd>
                        <dt class="col-sm-3">{{ __('Status') }}</dt>
                        <dd class="col-sm-9" id="tax-view-status">-</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <x-delete-modal id="taxDeleteModal" />

    @push('scripts')
        <script>
            document.querySelectorAll('.js-view-tax').forEach((button) => {
                button.addEventListener('click', () => {
                    document.getElementById('tax-view-code').textContent = button.dataset.code || '-';
                    document.getElementById('tax-view-name').textContent = button.dataset.name || '-';
                    document.getElementById('tax-view-rate').textContent = button.dataset.rate || '-';
                    document.getElementById('tax-view-inclusive').textContent = button.dataset.inclusive || '-';
                    document.getElementById('tax-view-status').textContent = button.dataset.status || '-';
                });
            });
        </script>
    @endpush
@endsection

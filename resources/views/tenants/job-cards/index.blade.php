@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Workshop')],
            ['label' => __('Job Cards')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Job Cards') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.job-cards.create') }}" class="btn btn-primary">{{ __('Add Job Card') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('tenant.job-cards.index') }}" class="row g-2 mb-3">
                <div class="col-md-10">
                    <label class="form-label" for="search">{{ __('Search') }}</label>
                    <input type="text" name="search" id="search" class="form-control" value="{{ request('search') }}"
                        placeholder="{{ __('Job no, customer, registration') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100">{{ __('Filter') }}</button>
                    <a href="{{ route('tenant.job-cards.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Job No') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Vehicle') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $jobCard)
                            <tr>
                                <td>{{ $jobCard->job_no }}</td>
                                <td>{{ $jobCard->job_date?->format('Y-m-d') }}</td>
                                <td>{{ $jobCard->customer?->name ?? '-' }}</td>
                                <td>{{ $jobCard->vehicle?->registration_no ?? '-' }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $jobCard->status->value)) }}</td>
                                <td class="text-end">
                                    <div class="btn-list">
                                        <a href="{{ route('tenant.job-cards.show', $jobCard) }}"
                                            class="btn btn-sm btn-icon btn-primary-light btn-wave waves-effect waves-light"
                                            title="{{ __('View') }}">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                        <a href="{{ route('tenant.job-cards.edit', $jobCard) }}"
                                            class="btn btn-sm btn-icon btn-secondary-light btn-wave waves-effect waves-light"
                                            title="{{ __('Edit') }}">
                                            <i class="ri-edit-line"></i>
                                        </a>
                                        <button type="button"
                                            class="btn btn-sm btn-icon btn-danger-light btn-wave waves-effect waves-light js-delete-modal"
                                            data-action="{{ route('tenant.job-cards.destroy', $jobCard) }}"
                                            data-name="{{ $jobCard->job_no }}" data-title="{{ __('Delete Job Card') }}"
                                            data-message="{{ __('Are you sure you want to delete this job card?') }}"
                                            data-bs-toggle="modal" data-bs-target="#jobCardDeleteModal"
                                            title="{{ __('Delete') }}">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">{{ __('No job cards found.') }}</td>
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

    <x-delete-modal id="jobCardDeleteModal" />
@endsection

@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [['label' => __('Workshop')], ['label' => __('Job Cards')]];
    @endphp

    <x-breadcrumb title="{{ __('Job Cards') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.job-cards.create') }}" class="btn btn-primary">{{ __('Add Job Card') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card custom-card border-0 shadow-sm h-100" data-ajax-table-search
        data-form-selector="#job-cards-search-form" data-input-selector="#job-cards-search"
        data-table-body-selector="#job-cards-table tbody" data-pagination-selector="[data-job-cards-pagination]"
        data-loading-selector="#job-cards-search-loading" data-search-param="search" data-debounce="350"
        data-min-loading-visible="220">
        <div class="card-header d-flex justify-content-between align-items-end flex-wrap gap-3">
            <div class="card-title mb-0">
                {{ __('Job Cards') }}
            </div>
            <form method="GET" action="{{ route('tenant.job-cards.index') }}" class="d-flex align-items-end gap-2 flex-wrap"
                id="job-cards-search-form">
                <div class="position-relative">
                    <input type="text" name="search" id="job-cards-search" class="form-control pe-5"
                        value="{{ request('search') }}" placeholder="{{ __('Job no, customer, registration') }}">
                    <span id="job-cards-search-loading"
                        class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted opacity-0 pe-none"
                        style="transition: opacity 0.2s ease;" aria-hidden="true">
                        <span class="spinner-border spinner-border-sm"></span>
                    </span>
                </div>
            </form>
        </div>
        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0" id="job-cards-table">
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
                                <td>@tenantDate($jobCard->job_date, 'Y-m-d', '')</td>
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

            <div class="mt-3" data-job-cards-pagination>
                {{ $items->links() }}
            </div>
        </div>
    </div>

    <x-delete-modal id="jobCardDeleteModal" />
@endsection

@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [['label' => __('Workshop')], ['label' => __('Job Card Parts')]];
    @endphp

    <x-breadcrumb title="{{ __('Job Card Parts') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.job-card-parts.create') }}" class="btn btn-primary">{{ __('Add Part Line') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card custom-card border-0 shadow-sm h-100"
        @include('components.ajax-table-attributes', ['formSelector' => '#job-card-parts-search-form', 'inputSelector' => '#job-card-parts-search', 'tableBodySelector' => '#job-card-parts-table tbody', 'paginationSelector' => '[data-job-card-parts-pagination]', 'loadingSelector' => '#job-card-parts-search-loading', 'searchParam' => 'search', 'debounce' => '350', 'minLoadingVisible' => '220'])>
        <div class="card-header d-flex justify-content-between align-items-end flex-wrap gap-3">
            <div class="card-title mb-0">
                {{ __('Job Card Parts') }}
            </div>
            <form method="GET" action="{{ route('tenant.job-card-parts.index') }}"
                class="d-flex align-items-end gap-2 flex-wrap" id="job-card-parts-search-form">
                <div class="position-relative">
                    <input type="text" name="search" id="job-card-parts-search" class="form-control pe-5"
                        value="{{ request('search') }}" placeholder="{{ __('Job card, product') }}">
                    <span id="job-card-parts-search-loading"
                        class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted opacity-0 pe-none"
                        style="transition: opacity 0.2s ease;" aria-hidden="true">
                        <span class="spinner-border spinner-border-sm"></span>
                    </span>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0" id="job-card-parts-table">
                    <thead>
                        <tr>
                            <th>{{ __('Job Card') }}</th>
                            <th>{{ __('Product') }}</th>
                            <x-sortable-column :label="__('Qty')" column="qty" :current-sort-by="$sortBy"
                                :current-sort-direction="$sortDirection" />
                            <x-sortable-column :label="__('Unit Price')" column="unit_price" :current-sort-by="$sortBy"
                                :current-sort-direction="$sortDirection" />
                            <x-sortable-column :label="__('Line Total')" column="line_total" :current-sort-by="$sortBy"
                                :current-sort-direction="$sortDirection" />
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $partLine)
                            <tr>
                                <td>{{ $partLine->jobCard?->job_no ?? '-' }}</td>
                                <td>{{ $partLine->product?->name ?? '-' }}</td>
                                <td>{{ number_format((float) $partLine->qty, 3) }}</td>
                                <td>{{ number_format((float) $partLine->unit_price, 2) }}</td>
                                <td>{{ number_format((float) $partLine->line_total, 2) }}</td>
                                <td class="text-end">
                                    <div class="btn-list">
                                        <a href="{{ route('tenant.job-card-parts.show', $partLine) }}"
                                            class="btn btn-sm btn-icon btn-primary-light btn-wave waves-effect waves-light"
                                            title="{{ __('View') }}">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                        <a href="{{ route('tenant.job-card-parts.edit', $partLine) }}"
                                            class="btn btn-sm btn-icon btn-secondary-light btn-wave waves-effect waves-light"
                                            title="{{ __('Edit') }}">
                                            <i class="ri-edit-line"></i>
                                        </a>
                                        <button type="button"
                                            class="btn btn-sm btn-icon btn-danger-light btn-wave waves-effect waves-light js-delete-modal"
                                            data-action="{{ route('tenant.job-card-parts.destroy', $partLine) }}"
                                            data-name="{{ $partLine->product?->name ?? '-' }}"
                                            data-title="{{ __('Delete Part Line') }}"
                                            data-message="{{ __('Are you sure you want to delete this part line?') }}"
                                            data-bs-toggle="modal" data-bs-target="#jobCardPartDeleteModal"
                                            title="{{ __('Delete') }}">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">{{ __('No part lines found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3" data-job-card-parts-pagination>
                {{ $items->links() }}
            </div>
        </div>
    </div>

    <x-delete-modal id="jobCardPartDeleteModal" />
@endsection

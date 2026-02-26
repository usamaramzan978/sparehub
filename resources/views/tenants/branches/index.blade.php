@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [['label' => __('Operations')], ['label' => __('Branches')]];
    @endphp

    <x-breadcrumb title="{{ __('Branches') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.branches.create') }}" class="btn btn-primary">{{ __('Add Branch') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card custom-card border-0 shadow-sm h-100" @include('components.ajax-table-attributes', [
        'formSelector' => '#branches-search-form',
        'inputSelector' => '#branches-search',
        'tableBodySelector' => '#branches-table tbody',
        'paginationSelector' => '[data-branches-pagination]',
        'loadingSelector' => '#branches-search-loading',
        'searchParam' => 'search',
        'debounce' => '350',
        'minLoadingVisible' => '220',
    ])>
        <div class="card-header d-flex justify-content-between align-items-end flex-wrap gap-3">
            <div class="card-title mb-0">
                {{ __('Branches') }}
            </div>
            <form method="GET" action="{{ route('tenant.branches.index') }}" class="d-flex align-items-end gap-2 flex-wrap"
                id="branches-search-form">
                <div class="position-relative">
                    <input type="text" name="search" id="branches-search" class="form-control pe-5"
                        value="{{ request('search') }}" placeholder="{{ __('Search by code, name') }}">
                    <span id="branches-search-loading"
                        class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted opacity-0 pe-none"
                        style="transition: opacity 0.2s ease;" aria-hidden="true">
                        <span class="spinner-border spinner-border-sm"></span>
                    </span>
                </div>
            </form>
        </div>
        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0" id="branches-table">
                    <thead>
                        <tr>
                            <x-sortable-column :label="__('Code')" column="code" :current-sort-by="$sortBy" :current-sort-direction="$sortDirection" />
                            <x-sortable-column :label="__('Name')" column="name" :current-sort-by="$sortBy" :current-sort-direction="$sortDirection" />
                            <th>{{ __('Warehouse') }}</th>
                            <x-sortable-column :label="__('Status')" column="status" :current-sort-by="$sortBy" :current-sort-direction="$sortDirection" />
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $branch)
                            <tr>
                                <td>{{ $branch->code }}</td>
                                <td>{{ $branch->name }}</td>
                                <td>{{ $branch->warehouse?->name ?? '-' }}</td>
                                <td>
                                    @if ($branch->status->value === 'active')
                                        <span class="badge bg-success-transparent">{{ __('Active') }}</span>
                                    @else
                                        <span class="badge bg-secondary-transparent">{{ __('Inactive') }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-list">
                                        <span class="d-inline-block" data-bs-toggle="tooltip" title="{{ __('View') }}">
                                            <a href="{{ route('tenant.branches.show', $branch) }}"
                                                class="btn btn-sm btn-icon btn-primary-light btn-wave waves-effect waves-light">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                        </span>
                                        <span class="d-inline-block" data-bs-toggle="tooltip" title="{{ __('Edit') }}">
                                            <a href="{{ route('tenant.branches.edit', $branch) }}"
                                                class="btn btn-sm btn-icon btn-secondary-light btn-wave waves-effect waves-light">
                                                <i class="ri-edit-line"></i>
                                            </a>
                                        </span>
                                        @if ($canDeleteBranch)
                                            <span class="d-inline-block" data-bs-toggle="tooltip"
                                                title="{{ __('Delete') }}">
                                                <button type="button"
                                                    class="btn btn-sm btn-icon btn-danger-light btn-wave waves-effect waves-light js-delete-modal"
                                                    data-action="{{ route('tenant.branches.destroy', $branch) }}"
                                                    data-name="{{ $branch->name }}" data-title="{{ __('Delete Branch') }}"
                                                    data-message="{{ __('Are you sure you want to delete this branch?') }}"
                                                    data-bs-toggle="modal" data-bs-target="#branchDeleteModal">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </span>
                                        @else
                                            <span class="d-inline-block" data-bs-toggle="tooltip"
                                                title="{{ __('At least one branch must remain') }}">
                                                <button type="button"
                                                    class="btn btn-sm btn-icon btn-danger-light btn-wave waves-effect waves-light"
                                                    disabled>
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">{{ __('No branches found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3" data-branches-pagination>
                {{ $items->links() }}
            </div>
        </div>
    </div>

    <x-delete-modal id="branchDeleteModal" />
@endsection

@extends('layouts.app')

@section('content')
    @include('tenants.categories.partials.create', ['parents' => $parents, 'statuses' => $statuses])

    @php
        $breadcrumbs = [['label' => __('Catalog')], ['label' => __('Categories')]];
    @endphp

    <x-breadcrumb title="{{ __('Categories') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryCreateModal">
                {{ __('Add Category') }}
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

    <div class="card custom-card border-0 shadow-sm h-100"
        @include('components.ajax-table-attributes', ['formSelector' => '#categories-search-form', 'inputSelector' => '#categories-search', 'tableBodySelector' => '#categories-table tbody', 'paginationSelector' => '[data-categories-pagination]', 'loadingSelector' => '#categories-search-loading', 'searchParam' => 'search', 'debounce' => '350', 'minLoadingVisible' => '220'])>
        <div class="card-header d-flex justify-content-between align-items-end flex-wrap gap-3">
            <div class="card-title mb-0">
                {{ __('Categories') }}
            </div>
            <form method="GET" action="{{ route('tenant.categories.index') }}"
                class="d-flex align-items-end gap-2 flex-wrap" id="categories-search-form">
                <div class="position-relative">
                    <input type="text" name="search" id="categories-search" class="form-control pe-5"
                        value="{{ request('search') }}" placeholder="{{ __('Search by name') }}">
                    <span id="categories-search-loading"
                        class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted opacity-0 pe-none"
                        style="transition: opacity 0.2s ease;" aria-hidden="true">
                        <span class="spinner-border spinner-border-sm"></span>
                    </span>
                </div>
            </form>
        </div>
        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0" id="categories-table">
                    <thead>
                        <tr>
                            <x-sortable-column :label="__('Name')" column="name" :current-sort-by="$sortBy"
                                :current-sort-direction="$sortDirection" />
                            <x-sortable-column :label="__('Products')" column="products_count" :current-sort-by="$sortBy"
                                :current-sort-direction="$sortDirection" />
                            <th>{{ __('Parent') }}</th>
                            <x-sortable-column :label="__('Status')" column="status" :current-sort-by="$sortBy"
                                :current-sort-direction="$sortDirection" />
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $category)
                            <tr>
                                <td>{{ $category->name }}</td>
                                <td>
                                    <span class="badge bg-primary-transparent">
                                        {{ (int) $category->products_count }} {{ __('Products') }}
                                    </span>
                                </td>
                                <td>{{ $category->parent?->name ?? '-' }}</td>
                                <td>
                                    @if ($category->status->value === 'active')
                                        <span class="badge bg-success-transparent">
                                            {{ __('Active') }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-transparent">
                                            {{ __('Inactive') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-list">
                                        <span class="d-inline-block" data-bs-toggle="tooltip" title="{{ __('View') }}">
                                            <button type="button"
                                                class="btn btn-sm btn-icon btn-primary-light btn-wave waves-effect waves-light js-view-category"
                                                data-name="{{ $category->name }}"
                                                data-parent="{{ $category->parent?->name ?? '-' }}"
                                                data-status="{{ $category->status->value }}" data-bs-toggle="modal"
                                                data-bs-target="#categoryViewModal">
                                                <i class="ri-eye-line"></i>
                                            </button>
                                        </span>
                                        <span class="d-inline-block" data-bs-toggle="tooltip" title="{{ __('Edit') }}">
                                            <button type="button"
                                                class="btn btn-sm btn-icon btn-secondary-light btn-wave waves-effect waves-light"
                                                data-bs-toggle="modal"
                                                data-bs-target="#categoryEditModal-{{ $category->id }}">
                                                <i class="ri-edit-line"></i>
                                            </button>
                                        </span>
                                        <span class="d-inline-block" data-bs-toggle="tooltip" title="{{ __('Delete') }}">
                                            <button type="button"
                                                class="btn btn-sm btn-icon btn-danger-light btn-wave waves-effect waves-light js-delete-modal"
                                                data-action="{{ route('tenant.categories.destroy', $category) }}"
                                                data-name="{{ $category->name }}" data-title="{{ __('Delete Category') }}"
                                                data-message="{{ __('Are you sure you want to delete this category?') }}"
                                                data-bs-toggle="modal" data-bs-target="#categoryDeleteModal">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            @include('tenants.categories.partials.edit', [
                                'category' => $category,
                                'parents' => $parents,
                                'statuses' => $statuses,
                            ])
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">{{ __('No categories found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3" data-categories-pagination>
                {{ $items->links() }}
            </div>
        </div>
    </div>

    <div class="modal fade" id="categoryViewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Category Details') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3">{{ __('Name') }}</dt>
                        <dd class="col-sm-9" id="category-view-name">-</dd>
                        <dt class="col-sm-3">{{ __('Parent') }}</dt>
                        <dd class="col-sm-9" id="category-view-parent">-</dd>
                        <dt class="col-sm-3">{{ __('Status') }}</dt>
                        <dd class="col-sm-9" id="category-view-status">-</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <x-delete-modal id="categoryDeleteModal" />

    @push('scripts')
        <script>
            document.addEventListener('click', (event) => {
                const target = event.target;

                if (!(target instanceof HTMLElement)) {
                    return;
                }

                const button = target.closest('.js-view-category');

                if (!button) {
                    return;
                }

                document.getElementById('category-view-name').textContent = button.dataset.name || '-';
                document.getElementById('category-view-parent').textContent = button.dataset.parent || '-';
                document.getElementById('category-view-status').textContent = button.dataset.status || '-';
            });
        </script>
    @endpush
@endsection

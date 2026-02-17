@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Operations')],
            ['label' => __('Branches')],
        ];
        $canDeleteBranch = $items->total() > 1;
    @endphp

    <x-breadcrumb title="{{ __('Branches') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.branches.create') }}" class="btn btn-primary">{{ __('Add Branch') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Warehouse') }}</th>
                            <th>{{ __('Status') }}</th>
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
                                            <span class="d-inline-block" data-bs-toggle="tooltip" title="{{ __('Delete') }}">
                                                <button type="button"
                                                    class="btn btn-sm btn-icon btn-danger-light btn-wave waves-effect waves-light js-delete-modal"
                                                    data-action="{{ route('tenant.branches.destroy', $branch) }}"
                                                    data-name="{{ $branch->name }}"
                                                    data-title="{{ __('Delete Branch') }}"
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

            <div class="mt-3">
                {{ $items->links() }}
            </div>
        </div>
    </div>

    <x-delete-modal id="branchDeleteModal" />
@endsection

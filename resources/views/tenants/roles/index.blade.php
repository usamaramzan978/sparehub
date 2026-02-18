@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [['label' => __('People')], ['label' => __('Roles')]];
    @endphp

    <x-breadcrumb title="{{ __('Roles') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.roles.create') }}" class="btn btn-primary">{{ __('Add Role') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Guard') }}</th>
                            <th>{{ __('Permissions') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $role)
                            <tr>
                                <td>{{ $role->name }}</td>
                                <td>{{ $role->guard_name }}</td>
                                <td>{{ $role->permissions->count() }}</td>
                                <td class="text-end">
                                    <div class="btn-list">
                                        <span class="d-inline-block" data-bs-toggle="tooltip" title="{{ __('View') }}">
                                            <a href="{{ route('tenant.roles.show', $role) }}"
                                                class="btn btn-sm btn-icon btn-primary-light btn-wave waves-effect waves-light">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                        </span>
                                        <span class="d-inline-block" data-bs-toggle="tooltip" title="{{ __('Edit') }}">
                                            <a href="{{ route('tenant.roles.edit', $role) }}"
                                                class="btn btn-sm btn-icon btn-secondary-light btn-wave waves-effect waves-light">
                                                <i class="ri-edit-line"></i>
                                            </a>
                                        </span>
                                        <span class="d-inline-block" data-bs-toggle="tooltip" title="{{ __('Delete') }}">
                                            <button type="button"
                                                class="btn btn-sm btn-icon btn-danger-light btn-wave waves-effect waves-light js-delete-modal"
                                                data-action="{{ route('tenant.roles.destroy', $role) }}"
                                                data-name="{{ $role->name }}" data-title="{{ __('Delete Role') }}"
                                                data-message="{{ __('Are you sure you want to delete this role?') }}"
                                                data-bs-toggle="modal" data-bs-target="#roleDeleteModal">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">{{ __('No roles found.') }}</td>
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

    <x-delete-modal id="roleDeleteModal" />
@endsection

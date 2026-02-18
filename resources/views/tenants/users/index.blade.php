@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [['label' => __('People')], ['label' => __('Users')]];
    @endphp

    <x-breadcrumb title="{{ __('Users') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.users.create') }}" class="btn btn-primary">{{ __('Add User') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-body">
            <form method="GET" action="{{ route('tenant.users.index') }}" class="row g-2 mb-3">
                <div class="col-md-5">
                    <label class="form-label" for="search">{{ __('Search') }}</label>
                    <input type="text" name="search" id="search" class="form-control" value="{{ request('search') }}"
                        placeholder="{{ __('Name, email, phone') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="status">{{ __('Status') }}</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">{{ __('All Statuses') }}</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                                {{ ucfirst($status->value) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="per_page">{{ __('Per Page') }}</label>
                    <select name="per_page" id="per_page" class="form-select">
                        @foreach ([15, 25, 50, 100] as $perPage)
                            <option value="{{ $perPage }}" @selected((int) request('per_page', 15) === $perPage)>
                                {{ $perPage }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100">{{ __('Filter') }}</button>
                    <a href="{{ route('tenant.users.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Email') }}</th>
                            <th>{{ __('Phone') }}</th>
                            <th>{{ __('Branch') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->phone ?: '-' }}</td>
                                <td>{{ $user->branch?->name ?: '-' }}</td>
                                <td>
                                    @if ($user->status->value === 'active')
                                        <span class="badge bg-success-transparent">{{ __('Active') }}</span>
                                    @elseif ($user->status->value === 'inactive')
                                        <span class="badge bg-secondary-transparent">{{ __('Inactive') }}</span>
                                    @else
                                        <span class="badge bg-danger-transparent">{{ __('Suspended') }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-list">
                                        <a href="{{ route('tenant.users.show', $user) }}"
                                            class="btn btn-sm btn-icon btn-primary-light btn-wave waves-effect waves-light"
                                            title="{{ __('View') }}">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                        <a href="{{ route('tenant.users.edit', $user) }}"
                                            class="btn btn-sm btn-icon btn-secondary-light btn-wave waves-effect waves-light"
                                            title="{{ __('Edit') }}">
                                            <i class="ri-edit-line"></i>
                                        </a>
                                        <button type="button"
                                            class="btn btn-sm btn-icon btn-danger-light btn-wave waves-effect waves-light js-delete-modal"
                                            data-action="{{ route('tenant.users.destroy', $user) }}"
                                            data-name="{{ $user->name }}" data-title="{{ __('Delete User') }}"
                                            data-message="{{ __('Are you sure you want to delete this user?') }}"
                                            data-bs-toggle="modal" data-bs-target="#userDeleteModal"
                                            title="{{ __('Delete') }}">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">{{ __('No users found.') }}</td>
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

    <x-delete-modal id="userDeleteModal" />
@endsection

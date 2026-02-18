@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('People')],
            ['label' => __('Roles'), 'url' => route('tenant.roles.index')],
            ['label' => __('Edit')],
        ];

        $selectedPermissions = $role->permissions->pluck('id')->all();
        $groupedPermissions = $permissions->groupBy(function ($permission) {
            $name = $permission->name ?? '';
            $parts = preg_split('/[\\.\\:\\-\\s]/', $name, 2);
            $group = $parts[0] ?? 'Other';
            $group = trim($group);

            return $group !== '' ? $group : 'Other';
        });
        $oldPermissions = collect(old('permissions', $selectedPermissions));
    @endphp

    <x-breadcrumb title="{{ __('Edit Role') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.roles.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card">
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('tenant.roles.update', $role) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="guard_name" value="{{ $role->guard_name }}">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="name">{{ __('Name') }}</label>
                        <input type="text" name="name" id="name"
                            class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $role->name) }}"
                            required>
                        @error('name')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ __('Permissions') }}</label>
                    @error('permissions')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                    <div class="row">
                        @foreach ($groupedPermissions as $group => $groupPermissions)
                            <div class="col-lg-4 col-md-6 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <div class="fw-semibold mb-2">{{ ucfirst(str_replace('_', ' ', $group)) }}</div>
                                    @foreach ($groupPermissions as $permission)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="permissions[]"
                                                value="{{ $permission->id }}" id="permission-{{ $permission->id }}"
                                                @checked($oldPermissions->contains($permission->id))>
                                            <label class="form-check-label" for="permission-{{ $permission->id }}">
                                                {{ $permission->name }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
            </form>
        </div>
    </div>
@endsection

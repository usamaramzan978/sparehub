@extends('layouts.app')

@section('content')
    @php
        use Illuminate\Support\Str;

        $breadcrumbs = [
            ['label' => __('Access Control')],
            ['label' => __('Roles'), 'url' => route('tenant.roles.index')],
            ['label' => __('Details')],
        ];

        $actionBadges = [
            'view' => 'bg-primary-transparent',
            'create' => 'bg-success-transparent',
            'update' => 'bg-warning-transparent',
            'delete' => 'bg-danger-transparent',
        ];

        $groupedPermissions = $role->permissions->groupBy(function ($permission) {
            $name = $permission->name ?? '';

            return str_contains($name, '.') ? explode('.', $name, 2)[0] : $name;
        });
    @endphp

    <x-breadcrumb title="{{ __('Role Details') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.roles.edit', $role) }}" class="btn btn-secondary">{{ __('Edit') }}</a>
            <a href="{{ route('tenant.roles.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="text-muted">{{ __('Name') }}</div>
                    <div class="fw-semibold">{{ $role->name }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted">{{ __('Guard') }}</div>
                    <div class="fw-semibold">{{ $role->guard_name }}</div>
                </div>
            </div>

            <h6 class="mb-3">{{ __('Permissions') }}</h6>
            @if ($role->permissions->isEmpty())
                <p class="text-muted mb-0">{{ __('No permissions assigned.') }}</p>
            @else
                <div class="row">
                    @foreach ($groupedPermissions as $group => $permissions)
                        <div class="col-lg-4 col-md-6 mb-3">
                            <div class="border rounded p-3 h-100">
                                <div class="fw-semibold mb-2">{{ Str::headline($group) }}</div>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ($permissions as $permission)
                                        @php
                                            $action = (string) Str::of($permission->name)->after('.');
                                            $badge = $actionBadges[$action] ?? 'bg-info-transparent';
                                        @endphp
                                        <span class="badge {{ $badge }}">{{ Str::headline($action) }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection

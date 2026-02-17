@extends('layouts.app')

@section('content')
    @php
        use Illuminate\Support\Str;

        $breadcrumbs = [
            ['label' => __('Access Control')],
            ['label' => __('Roles'), 'url' => route('roles.index')],
            ['label' => __('Create')],
        ];

        $actionBadges = [
            'view' => 'bg-primary-transparent',
            'create' => 'bg-success-transparent',
            'update' => 'bg-warning-transparent',
            'delete' => 'bg-danger-transparent',
        ];
    @endphp

    <x-breadcrumb title="{{ __('Create Role') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
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

            <form method="POST" action="{{ route('roles.store') }}">
                @csrf
                <input type="hidden" name="guard_name" value="web">

                @php
                    $groupedPermissions = $permissions->groupBy(function ($permission) {
                        $name = $permission->name ?? '';

                        return str_contains($name, '.') ? explode('.', $name, 2)[0] : $name;
                    });
                    $oldPermissions = collect(old('permissions'));
                @endphp

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="name">{{ __('Name') }}</label>
                        <input type="text" name="name" id="name"
                            class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
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
                            <div class="col-lg-3 col-md-4 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <div class="fw-semibold mb-2">{{ Str::headline($group) }}</div>
                                    @foreach ($groupPermissions as $permission)
                                        @php
                                            $action = (string) Str::of($permission->name)->after('.');
                                            $badge = $actionBadges[$action] ?? 'bg-info-transparent';
                                        @endphp
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="permissions[]"
                                                value="{{ $permission->id }}" id="permission-{{ $permission->id }}"
                                                @checked($oldPermissions->contains($permission->id))>
                                            <label class="form-check-label" for="permission-{{ $permission->id }}">
                                                <span
                                                    class="badge {{ $badge }}">{{ Str::headline($action) }}</span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">{{ __('Create') }}</button>
            </form>
        </div>
    </div>
@endsection

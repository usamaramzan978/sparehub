@extends('layouts.app')

@section('content')
    @php
        use Illuminate\Support\Str;

        $breadcrumbs = [['label' => __('Access Control')], ['label' => __('Permissions')]];

        $actionOrder = ['view', 'create', 'update', 'delete'];
        $actionBadges = [
            'view' => 'bg-primary-transparent',
            'create' => 'bg-success-transparent',
            'update' => 'bg-warning-transparent',
            'delete' => 'bg-danger-transparent',
        ];
    @endphp

    <x-breadcrumb title="{{ __('Permissions') }}" :items="$breadcrumbs">
    </x-breadcrumb>

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Module') }}</th>
                            <th>{{ __('Permissions') }}</th>
                            <th>{{ __('Guard') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($groups as $groupName => $permissions)
                            @php
                                $sorted = $permissions->sortBy(function ($permission) use ($actionOrder) {
                                    $action = Str::of($permission->name)->after('.');
                                    $index = array_search($action, $actionOrder, true);

                                    return $index === false ? 999 : $index;
                                });

                                $guard = optional($permissions->first())->guard_name;
                                $actions = $sorted
                                    ->map(function ($permission) {
                                        return (string) Str::of($permission->name)->after('.');
                                    })
                                    ->values();
                            @endphp
                            <tr>
                                <td>{{ Str::headline($groupName) }}</td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach ($sorted as $permission)
                                            @php
                                                $action = (string) Str::of($permission->name)->after('.');
                                                $badge = $actionBadges[$action] ?? 'bg-info-transparent';
                                            @endphp
                                            <span class="badge {{ $badge }}">{{ Str::headline($action) }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td>{{ $guard }}</td>
                                <td class="text-end">
                                    <div class="btn-list">
                                        <span class="d-inline-block" data-bs-toggle="tooltip" title="{{ __('View') }}">
                                            <button type="button"
                                                class="btn btn-sm btn-icon btn-primary-light btn-wave waves-effect waves-light js-view-permission"
                                                data-module="{{ Str::headline($groupName) }}"
                                                data-guard="{{ $guard }}"
                                                data-permissions='@json($actions)' data-bs-toggle="modal"
                                                data-bs-target="#permissionViewModal">
                                                <i class="ri-eye-line"></i>
                                            </button>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">{{ __('No permissions found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <div class="modal fade" id="permissionViewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Permission Details') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3">{{ __('Module') }}</dt>
                        <dd class="col-sm-9" id="permission-view-module">-</dd>
                        <dt class="col-sm-3">{{ __('Permissions') }}</dt>
                        <dd class="col-sm-9">
                            <div id="permission-view-permissions" class="d-flex flex-wrap gap-2">-</div>
                        </dd>
                        <dt class="col-sm-3">{{ __('Guard') }}</dt>
                        <dd class="col-sm-9" id="permission-view-guard">-</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.querySelectorAll('.js-view-permission').forEach((button) => {
                button.addEventListener('click', () => {
                    document.getElementById('permission-view-module').textContent = button.dataset.module ||
                    '-';
                    document.getElementById('permission-view-guard').textContent = button.dataset.guard || '-';

                    const container = document.getElementById('permission-view-permissions');
                    const permissions = JSON.parse(button.dataset.permissions || '[]');

                    if (!container) {
                        return;
                    }

                    container.innerHTML = '';

                    if (!permissions.length) {
                        container.textContent = '-';
                        return;
                    }

                    const badgeMap = {
                        view: 'bg-primary-transparent',
                        create: 'bg-success-transparent',
                        update: 'bg-warning-transparent',
                        delete: 'bg-danger-transparent',
                    };

                    permissions.forEach((permission) => {
                        const badge = document.createElement('span');
                        const key = (permission || '').toLowerCase();
                        badge.className = `badge ${badgeMap[key] || 'bg-info-transparent'}`;
                        badge.textContent = permission.replace(/[-_]/g, ' ').replace(/\b\w/g, (c) => c
                            .toUpperCase());
                        container.appendChild(badge);
                    });
                });
            });
        </script>
    @endpush
@endsection

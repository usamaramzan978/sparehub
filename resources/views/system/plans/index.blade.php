@extends('layouts.system')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h4 class="mb-1">{{ __('Plans') }}</h4>
            <p class="text-muted mb-0">{{ __('Manage subscription plans offered to tenants.') }}</p>
        </div>
        <a href="{{ route('system.plans.create') }}" class="btn btn-primary">{{ __('Create Plan') }}</a>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Plan') }}</th>
                            <th>{{ __('Pricing') }}</th>
                            <th>{{ __('Limits') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($plans as $plan)
                            <tr>
                                <td>{{ $plan->code }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $plan->name }}</div>
                                    <small class="text-muted">{{ $plan->description ?: '-' }}</small>
                                </td>
                                <td>
                                    <div>{{ __('Monthly') }}: {{ number_format((float) $plan->monthly_price, 2) }}</div>
                                    <small class="text-muted">{{ __('Annual') }}: {{ $plan->annual_price !== null ? number_format((float) $plan->annual_price, 2) : '-' }}</small>
                                </td>
                                <td>
                                    <div>{{ __('Users') }}: {{ $plan->max_users ?? '∞' }}</div>
                                    <small class="text-muted">{{ __('Branches') }}: {{ $plan->max_branches ?? '∞' }}</small>
                                </td>
                                <td><span class="badge bg-secondary-transparent">{{ strtoupper($plan->status?->value ?? (string) $plan->status) }}</span></td>
                                <td class="text-end">
                                    <div class="btn-list justify-content-end">
                                        <a href="{{ route('system.plans.edit', $plan) }}" class="btn btn-sm btn-secondary-light">
                                            <i class="ri-edit-line"></i>
                                        </a>
                                        <form method="POST" action="{{ route('system.plans.destroy', $plan) }}"
                                            onsubmit="return confirm('Delete this plan?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger-light"
                                                @if ($plan->tenants_count > 0) disabled @endif>
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">{{ __('No plans found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">{{ $plans->links() }}</div>
    </div>
@endsection

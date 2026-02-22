@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [['label' => __('System')], ['label' => __('Recent Activity')]];
    @endphp

    <x-breadcrumb title="{{ __('Recent Activity') }}" :items="$breadcrumbs" />

    <div class="row">
        <div class="col-12 col-xxl-8">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">{{ __('Recent Activity') }}</div>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled recent-activity-list mb-0">
                        @forelse ($activities as $activity)
                            <li class="recent-activity-list__item">
                                <div class="d-flex justify-content-between align-items-start gap-2 pe-2">
                                    <div>
                                        <span class="badge {{ $activity['badge_class'] }} mb-2">
                                            {{ __($activity['title']) }}
                                        </span>
                                        <span class="d-block">
                                            {{ __($activity['description']) }}
                                        </span>
                                    </div>
                                    <div class="recent-activity-time fs-13 text-end">
                                        <span class="text-muted d-block">{{ $activity['time'] }}</span>
                                        <span class="text-muted">{{ $activity['date'] }}</span>
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="text-muted py-2">{{ __('No recent activity found.') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .recent-activity-list__item {
            padding: 0.85rem 0;
            border-bottom: 1px dashed var(--default-border);
        }

        .recent-activity-list__item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
    </style>
@endpush


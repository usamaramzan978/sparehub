@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Workshop')],
            ['label' => __('Job Cards'), 'url' => route('tenant.job-cards.index')],
            ['label' => __('Create')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Create Job Card') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.job-cards.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card custom-card border-0 shadow-sm h-100">
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

            @include('tenants.job-cards.partials.form', [
                'formAction' => route('tenant.job-cards.store'),
                'formMethod' => 'POST',
                'submitLabel' => __('Create'),
                'jobCard' => null,
            ])
        </div>
    </div>
@endsection

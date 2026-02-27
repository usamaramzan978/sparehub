@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('People')],
            ['label' => __('Users'), 'url' => route('tenant.users.index')],
            ['label' => __('Create')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Create User') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.users.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
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

            @include('tenants.users.partials.form', [
                'formAction' => route('tenant.users.store'),
                'formMethod' => 'POST',
                'submitLabel' => __('Create'),
                'user' => null,
                'statuses' => $statuses,
                'branch' => $branch,
                'labourServices' => $labourServices,
            ])
        </div>
    </div>
@endsection

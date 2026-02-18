@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Purchases')],
            ['label' => __('Purchase Orders'), 'url' => route('tenant.purchases.index')],
            ['label' => __('Create')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Create Purchase') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.purchases.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-body">
            @include('tenants.purchases.partials.form', [
                'formAction' => route('tenant.purchases.store'),
                'formMethod' => 'POST',
                'submitLabel' => __('Create'),
                'purchase' => null,
            ])
        </div>
    </div>
@endsection

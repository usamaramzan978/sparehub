@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Purchases')],
            ['label' => __('Purchase Returns'), 'url' => route('tenant.purchase-returns.index')],
            ['label' => __('Create')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Create Purchase Return') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.purchase-returns.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card">
        <div class="card-body">
            @include('tenants.purchase-returns.partials.form', [
                'formAction' => route('tenant.purchase-returns.store'),
                'formMethod' => 'POST',
                'submitLabel' => __('Create'),
            ])
        </div>
    </div>
@endsection

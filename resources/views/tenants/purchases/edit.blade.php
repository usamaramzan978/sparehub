@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Purchases')],
            ['label' => __('Purchase Orders'), 'url' => route('tenant.purchases.index')],
            ['label' => __('Edit')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Edit Purchase') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.purchases.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card">
        <div class="card-body">
            @include('tenants.purchases.partials.form', [
                'formAction' => route('tenant.purchases.update', $purchase),
                'formMethod' => 'PUT',
                'submitLabel' => __('Update'),
                'purchase' => $purchase,
            ])
        </div>
    </div>
@endsection

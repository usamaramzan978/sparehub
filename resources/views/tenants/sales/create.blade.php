@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Sales')],
            ['label' => __('Sale Invoices'), 'url' => route('tenant.sales.index')],
            ['label' => __('Create')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Create Sale Invoice') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.sales.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card">
        <div class="card-body">
            @include('tenants.sales.partials.form', [
                'formAction' => route('tenant.sales.store'),
                'formMethod' => 'POST',
                'submitLabel' => __('Create'),
                'sale' => null,
            ])
        </div>
    </div>
@endsection

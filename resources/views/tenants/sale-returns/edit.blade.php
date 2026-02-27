@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Sales')],
            ['label' => __('Sales Returns'), 'url' => route('tenant.sale-returns.index')],
            ['label' => __('Edit')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Edit Sales Return') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.sale-returns.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-body">
            @include('tenants.sale-returns.partials.form', [
                'formAction' => route('tenant.sale-returns.update', $saleReturn),
                'formMethod' => 'PUT',
                'submitLabel' => __('Update'),
                'saleReturn' => $saleReturn,
            ])
        </div>
    </div>
@endsection

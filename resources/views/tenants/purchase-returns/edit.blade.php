@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Purchases')],
            ['label' => __('Purchase Returns'), 'url' => route('tenant.purchase-returns.index')],
            ['label' => __('Edit')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Edit Purchase Return') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.purchase-returns.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card">
        <div class="card-body">
            @include('tenants.purchase-returns.partials.form', [
                'purchaseReturn' => $purchaseReturn,
                'formAction' => route('tenant.purchase-returns.update', $purchaseReturn),
                'formMethod' => 'PUT',
                'submitLabel' => __('Update'),
            ])
        </div>
    </div>
@endsection

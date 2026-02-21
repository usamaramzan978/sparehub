@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Support')],
            ['label' => __('Support Tickets'), 'url' => route('tenant.support-tickets.index')],
            ['label' => __('Create')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Create Support Ticket') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.support-tickets.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-body">
            @include('tenants.support-tickets.partials.form', [
                'action' => route('tenant.support-tickets.store'),
                'method' => 'POST',
                'supportTicket' => null,
                'submitLabel' => __('Create'),
            ])
        </div>
    </div>
@endsection

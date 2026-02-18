@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Catalog')],
            ['label' => __('Service Catalog'), 'url' => route('tenant.service-catalog.index')],
            ['label' => __('Create')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Add Service') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.service-catalog.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
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

            <form method="POST" action="{{ route('tenant.service-catalog.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="code">{{ __('Code') }}</label>
                        <input type="text" name="code" id="code"
                            class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" required>
                        @error('code')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-5 mb-3">
                        <label class="form-label" for="name">{{ __('Name') }}</label>
                        <input type="text" name="name" id="name"
                            class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="category">{{ __('Category') }}</label>
                        <input type="text" name="category" id="category"
                            class="form-control @error('category') is-invalid @enderror" value="{{ old('category') }}">
                        @error('category')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="base_price">{{ __('Base Price') }}</label>
                        <input type="number" step="0.01" min="0" name="base_price" id="base_price"
                            class="form-control @error('base_price') is-invalid @enderror"
                            value="{{ old('base_price', '0') }}" required>
                        @error('base_price')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="duration_minutes">{{ __('Duration (Min)') }}</label>
                        <input type="number" min="1" name="duration_minutes" id="duration_minutes"
                            class="form-control @error('duration_minutes') is-invalid @enderror"
                            value="{{ old('duration_minutes') }}">
                        @error('duration_minutes')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="default_tax_id">{{ __('Default Tax') }}</label>
                        <select name="default_tax_id" id="default_tax_id"
                            class="form-select singl-select-2 @error('default_tax_id') is-invalid @enderror">
                            <option value="">{{ __('No tax') }}</option>
                            @foreach ($taxes as $tax)
                                <option value="{{ $tax->id }}" @selected(old('default_tax_id') === $tax->id)>
                                    {{ $tax->name }} ({{ $tax->rate }}%)
                                </option>
                            @endforeach
                        </select>
                        @error('default_tax_id')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="status">{{ __('Status') }}</label>
                        <select name="status" id="status"
                            class="form-select singl-select-2 @error('status') is-invalid @enderror" required>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" @selected(old('status', 'active') === $status->value)>
                                    {{ ucfirst($status->value) }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-12 mb-3">
                        <div class="form-check">
                            <input type="hidden" name="is_taxable" value="0">
                            <input class="form-check-input" type="checkbox" value="1" id="is_taxable" name="is_taxable"
                                @checked((bool) old('is_taxable', true))>
                            <label class="form-check-label" for="is_taxable">{{ __('Taxable Service') }}</label>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">{{ __('Create') }}</button>
            </form>
        </div>
    </div>
@endsection

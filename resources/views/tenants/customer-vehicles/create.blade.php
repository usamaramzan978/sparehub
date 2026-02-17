@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('People')],
            ['label' => __('Customer Vehicles'), 'url' => route('tenant.customer-vehicles.index')],
            ['label' => __('Create')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Create Vehicle') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.customer-vehicles.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card">
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

            <form method="POST" action="{{ route('tenant.customer-vehicles.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="customer_id">{{ __('Customer') }}</label>
                        <select name="customer_id" id="customer_id"
                            class="form-select singl-select-2 @error('customer_id') is-invalid @enderror" required>
                            <option value="">{{ __('Select customer') }}</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" @selected(old('customer_id') === $customer->id)>
                                    {{ $customer->name }} ({{ $customer->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('customer_id')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="registration_no">{{ __('Registration No') }}</label>
                        <input type="text" name="registration_no" id="registration_no"
                            class="form-control @error('registration_no') is-invalid @enderror"
                            value="{{ old('registration_no') }}" required>
                        @error('registration_no')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="model">{{ __('Model') }}</label>
                        <input type="text" name="model" id="model"
                            class="form-control @error('model') is-invalid @enderror" value="{{ old('model') }}">
                        @error('model')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="year">{{ __('Year') }}</label>
                        <input type="number" name="year" id="year"
                            class="form-control @error('year') is-invalid @enderror" value="{{ old('year') }}">
                        @error('year')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="meter_reading">{{ __('Meter Reading') }}</label>
                        <input type="number" step="0.001" min="0" name="meter_reading" id="meter_reading"
                            class="form-control @error('meter_reading') is-invalid @enderror"
                            value="{{ old('meter_reading', '0') }}">
                        @error('meter_reading')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="chassis_no">{{ __('Chassis No') }}</label>
                        <input type="text" name="chassis_no" id="chassis_no"
                            class="form-control @error('chassis_no') is-invalid @enderror"
                            value="{{ old('chassis_no') }}">
                        @error('chassis_no')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="engine_no">{{ __('Engine No') }}</label>
                        <input type="text" name="engine_no" id="engine_no"
                            class="form-control @error('engine_no') is-invalid @enderror"
                            value="{{ old('engine_no') }}">
                        @error('engine_no')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">{{ __('Create') }}</button>
            </form>
        </div>
    </div>
@endsection

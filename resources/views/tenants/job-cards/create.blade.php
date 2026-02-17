@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Workshop')],
            ['label' => __('Job Cards'), 'url' => route('tenant.job-cards.index')],
            ['label' => __('Create')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Create Job Card') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.job-cards.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
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

            <form method="POST" action="{{ route('tenant.job-cards.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="job_no">{{ __('Job No') }}</label>
                        <input type="text" name="job_no" id="job_no"
                            class="form-control @error('job_no') is-invalid @enderror" value="{{ old('job_no') }}"
                            required>
                        @error('job_no')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="job_date">{{ __('Job Date') }}</label>
                        <input type="date" name="job_date" id="job_date"
                            class="form-control @error('job_date') is-invalid @enderror"
                            value="{{ old('job_date', now()->toDateString()) }}" required>
                        @error('job_date')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-3 mb-3">
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
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="vehicle_id">{{ __('Vehicle') }}</label>
                        <select name="vehicle_id" id="vehicle_id"
                            class="form-select singl-select-2 @error('vehicle_id') is-invalid @enderror">
                            <option value="">{{ __('No vehicle') }}</option>
                            @foreach ($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" @selected(old('vehicle_id') === $vehicle->id)>
                                    {{ $vehicle->registration_no }}
                                </option>
                            @endforeach
                        </select>
                        @error('vehicle_id')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="assigned_employee_id">{{ __('Assigned Employee') }}</label>
                        <select name="assigned_employee_id" id="assigned_employee_id"
                            class="form-select singl-select-2 @error('assigned_employee_id') is-invalid @enderror">
                            <option value="">{{ __('Unassigned') }}</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}" @selected(old('assigned_employee_id') === $employee->id)>
                                    {{ $employee->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('assigned_employee_id')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="status">{{ __('Status') }}</label>
                        <select name="status" id="status"
                            class="form-select singl-select-2 @error('status') is-invalid @enderror" required>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" @selected(old('status', 'new') === $status->value)>
                                    {{ ucfirst(str_replace('_', ' ', $status->value)) }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="meter_reading">{{ __('Meter Reading') }}</label>
                        <input type="number" step="0.001" min="0" name="meter_reading" id="meter_reading"
                            class="form-control @error('meter_reading') is-invalid @enderror"
                            value="{{ old('meter_reading') }}">
                        @error('meter_reading')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="next_reading">{{ __('Next Reading') }}</label>
                        <input type="number" step="0.001" min="0" name="next_reading" id="next_reading"
                            class="form-control @error('next_reading') is-invalid @enderror"
                            value="{{ old('next_reading') }}">
                        @error('next_reading')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-2 mb-3">
                        <label class="form-label" for="total_visits">{{ __('Visits') }}</label>
                        <input type="number" min="0" name="total_visits" id="total_visits"
                            class="form-control @error('total_visits') is-invalid @enderror"
                            value="{{ old('total_visits', 0) }}">
                        @error('total_visits')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-5 mb-3">
                        <label class="form-label" for="in_time">{{ __('In Time') }}</label>
                        <input type="datetime-local" name="in_time" id="in_time"
                            class="form-control @error('in_time') is-invalid @enderror" value="{{ old('in_time') }}">
                        @error('in_time')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-5 mb-3">
                        <label class="form-label" for="out_time">{{ __('Out Time') }}</label>
                        <input type="datetime-local" name="out_time" id="out_time"
                            class="form-control @error('out_time') is-invalid @enderror" value="{{ old('out_time') }}">
                        @error('out_time')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label" for="remarks">{{ __('Remarks') }}</label>
                        <textarea name="remarks" id="remarks" rows="2" class="form-control @error('remarks') is-invalid @enderror">{{ old('remarks') }}</textarea>
                        @error('remarks')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">{{ __('Create') }}</button>
            </form>
        </div>
    </div>
@endsection

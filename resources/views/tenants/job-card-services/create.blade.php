@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Workshop')],
            ['label' => __('Job Card Services'), 'url' => route('tenant.job-card-services.index')],
            ['label' => __('Create')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Create Service Line') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.job-card-services.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
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

            <form method="POST" action="{{ route('tenant.job-card-services.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="job_card_id">{{ __('Job Card') }}</label>
                        <select name="job_card_id" id="job_card_id"
                            class="form-select singl-select-2 @error('job_card_id') is-invalid @enderror" required>
                            <option value="">{{ __('Select job card') }}</option>
                            @foreach ($jobCards as $jobCard)
                                <option value="{{ $jobCard->id }}" @selected(old('job_card_id') === $jobCard->id)>
                                    {{ $jobCard->job_no }} - {{ $jobCard->customer?->name ?? '-' }}
                                </option>
                            @endforeach
                        </select>
                        @error('job_card_id')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="service_catalog_id">{{ __('Service Catalog') }}</label>
                        <select name="service_catalog_id" id="service_catalog_id"
                            class="form-select singl-select-2 @error('service_catalog_id') is-invalid @enderror">
                            <option value="">{{ __('Custom service') }}</option>
                            @foreach ($serviceCatalogs as $serviceCatalog)
                                <option value="{{ $serviceCatalog->id }}" @selected(old('service_catalog_id') === $serviceCatalog->id)>
                                    {{ $serviceCatalog->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('service_catalog_id')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="technician_id">{{ __('Technician') }}</label>
                        <select name="technician_id" id="technician_id"
                            class="form-select singl-select-2 @error('technician_id') is-invalid @enderror">
                            <option value="">{{ __('Unassigned') }}</option>
                            @foreach ($technicians as $technician)
                                <option value="{{ $technician->id }}" @selected(old('technician_id') === $technician->id)>
                                    {{ $technician->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('technician_id')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="service_name">{{ __('Service Name') }}</label>
                        <input type="text" name="service_name" id="service_name"
                            class="form-control @error('service_name') is-invalid @enderror"
                            value="{{ old('service_name') }}" required>
                        @error('service_name')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label" for="qty">{{ __('Qty') }}</label>
                        <input type="number" step="0.001" min="0.001" name="qty" id="qty"
                            class="form-control @error('qty') is-invalid @enderror" value="{{ old('qty', '1') }}" required>
                        @error('qty')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label" for="rate">{{ __('Rate') }}</label>
                        <input type="number" step="0.01" min="0" name="rate" id="rate"
                            class="form-control @error('rate') is-invalid @enderror" value="{{ old('rate', '0') }}"
                            required>
                        @error('rate')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label" for="status">{{ __('Status') }}</label>
                        <select name="status" id="status"
                            class="form-select singl-select-2 @error('status') is-invalid @enderror" required>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" @selected(old('status', 'pending') === $status->value)>
                                    {{ ucfirst(str_replace('_', ' ', $status->value)) }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
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

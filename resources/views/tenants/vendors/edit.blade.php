@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('People')],
            ['label' => __('Vendors'), 'url' => route('tenant.vendors.index')],
            ['label' => __('Edit')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Edit Vendor') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.vendors.show', $vendor) }}" class="btn btn-outline-primary">{{ __('Details') }}</a>
            <a href="{{ route('tenant.vendors.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
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

            <form method="POST" action="{{ route('tenant.vendors.update', $vendor) }}">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="code">{{ __('Code') }}</label>
                        <input type="text" name="code" id="code"
                            class="form-control @error('code') is-invalid @enderror"
                            value="{{ old('code', $vendor->code) }}" required>
                        @error('code')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-5 mb-3">
                        <label class="form-label" for="name">{{ __('Name') }}</label>
                        <input type="text" name="name" id="name"
                            class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $vendor->name) }}" required>
                        @error('name')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="phone">{{ __('Phone') }}</label>
                        <input type="text" name="phone" id="phone"
                            class="form-control @error('phone') is-invalid @enderror"
                            value="{{ old('phone', $vendor->phone) }}">
                        @error('phone')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="email">{{ __('Email') }}</label>
                        <input type="email" name="email" id="email"
                            class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email', $vendor->email) }}">
                        @error('email')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="cnic">{{ __('CNIC') }}</label>
                        <input type="text" name="cnic" id="cnic"
                            class="form-control @error('cnic') is-invalid @enderror"
                            value="{{ old('cnic', $vendor->cnic) }}">
                        @error('cnic')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="ntn">{{ __('NTN') }}</label>
                        <input type="text" name="ntn" id="ntn"
                            class="form-control @error('ntn') is-invalid @enderror" value="{{ old('ntn', $vendor->ntn) }}">
                        @error('ntn')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="city">{{ __('City') }}</label>
                        <input type="text" name="city" id="city"
                            class="form-control @error('city') is-invalid @enderror"
                            value="{{ old('city', $vendor->city) }}">
                        @error('city')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="opening_balance">{{ __('Opening Balance') }}</label>
                        <input type="number" step="0.01" name="opening_balance" id="opening_balance"
                            class="form-control @error('opening_balance') is-invalid @enderror"
                            value="{{ old('opening_balance', (string) $vendor->opening_balance) }}">
                        @error('opening_balance')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="status">{{ __('Status') }}</label>
                        <select name="status" id="status"
                            class="form-select singl-select-2 @error('status') is-invalid @enderror" required>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" @selected(old('status', $vendor->status->value) === $status->value)>
                                    {{ ucfirst($status->value) }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label" for="address">{{ __('Address') }}</label>
                        <textarea name="address" id="address" rows="2" class="form-control @error('address') is-invalid @enderror">{{ old('address', $vendor->address) }}</textarea>
                        @error('address')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
            </form>
        </div>
    </div>
@endsection

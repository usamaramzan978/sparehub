@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('People')],
            ['label' => __('Customers'), 'url' => route('tenant.customers.index')],
            ['label' => __('Edit')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Edit Customer') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.customers.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
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

            <form method="POST" action="{{ route('tenant.customers.update', $customer) }}">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="code">{{ __('Code') }}</label>
                        <input type="text" name="code" id="code"
                            class="form-control @error('code') is-invalid @enderror"
                            value="{{ old('code', $customer->code) }}" required>
                        @error('code')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-5 mb-3">
                        <label class="form-label" for="name">{{ __('Name') }}</label>
                        <input type="text" name="name" id="name"
                            class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $customer->name) }}" required>
                        @error('name')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="phone">{{ __('Phone') }}</label>
                        <input type="text" name="phone" id="phone"
                            class="form-control @error('phone') is-invalid @enderror"
                            value="{{ old('phone', $customer->phone) }}">
                        @error('phone')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="email">{{ __('Email') }}</label>
                        <input type="email" name="email" id="email"
                            class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email', $customer->email) }}">
                        @error('email')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="cnic">{{ __('CNIC') }}</label>
                        <input type="text" name="cnic" id="cnic"
                            class="form-control @error('cnic') is-invalid @enderror"
                            value="{{ old('cnic', $customer->cnic) }}">
                        @error('cnic')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="ntn">{{ __('NTN') }}</label>
                        <input type="text" name="ntn" id="ntn"
                            class="form-control @error('ntn') is-invalid @enderror"
                            value="{{ old('ntn', $customer->ntn) }}">
                        @error('ntn')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="city">{{ __('City') }}</label>
                        <input type="text" name="city" id="city"
                            class="form-control @error('city') is-invalid @enderror"
                            value="{{ old('city', $customer->city) }}">
                        @error('city')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="credit_limit">{{ __('Credit Limit') }}</label>
                        <input type="number" step="0.01" min="0" name="credit_limit" id="credit_limit"
                            class="form-control @error('credit_limit') is-invalid @enderror"
                            value="{{ old('credit_limit', (string) $customer->credit_limit) }}">
                        @error('credit_limit')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="opening_balance">{{ __('Opening Balance') }}</label>
                        <input type="number" step="0.01" name="opening_balance" id="opening_balance"
                            class="form-control @error('opening_balance') is-invalid @enderror"
                            value="{{ old('opening_balance', (string) $customer->opening_balance) }}">
                        @error('opening_balance')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-8 mb-3">
                        <label class="form-label" for="address">{{ __('Address') }}</label>
                        <textarea name="address" id="address" rows="2" class="form-control @error('address') is-invalid @enderror">{{ old('address', $customer->address) }}</textarea>
                        @error('address')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="status">{{ __('Status') }}</label>
                        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror"
                            required>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" @selected(old('status', $customer->status->value) === $status->value)>
                                    {{ ucfirst($status->value) }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">{{ __('SAVE') }}</button>
            </form>
        </div>
    </div>
@endsection

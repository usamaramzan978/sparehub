@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Purchases')],
            ['label' => __('Vendor Payments'), 'url' => route('tenant.vendor-payments.index')],
            ['label' => __('Create')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Create Vendor Payment') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.vendor-payments.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
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

            <form method="POST" action="{{ route('tenant.vendor-payments.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="payment_no">{{ __('Payment No') }}</label>
                        <input type="text" name="payment_no" id="payment_no"
                            class="form-control @error('payment_no') is-invalid @enderror" value="{{ old('payment_no') }}" required>
                        @error('payment_no')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="vendor_id">{{ __('Vendor') }}</label>
                        <select name="vendor_id" id="vendor_id"
                            class="form-select singl-select-2 @error('vendor_id') is-invalid @enderror" required>
                            <option value="">{{ __('Select vendor') }}</option>
                            @foreach ($vendors as $vendor)
                                <option value="{{ $vendor->id }}" @selected(old('vendor_id') === $vendor->id)>
                                    {{ $vendor->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('vendor_id')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="purchase_id">{{ __('Purchase') }}</label>
                        <select name="purchase_id" id="purchase_id"
                            class="form-select singl-select-2 @error('purchase_id') is-invalid @enderror">
                            <option value="">{{ __('None') }}</option>
                            @foreach ($purchases as $purchase)
                                <option value="{{ $purchase->id }}" @selected(old('purchase_id') === $purchase->id)>
                                    {{ $purchase->purchase_no }}
                                </option>
                            @endforeach
                        </select>
                        @error('purchase_id')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="payment_method">{{ __('Payment Method') }}</label>
                        <select name="payment_method" id="payment_method"
                            class="form-select singl-select-2 @error('payment_method') is-invalid @enderror" required>
                            @foreach ($methods as $method)
                                <option value="{{ $method->value }}" @selected(old('payment_method', 'cash') === $method->value)>
                                    {{ ucfirst($method->value) }}
                                </option>
                            @endforeach
                        </select>
                        @error('payment_method')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="amount">{{ __('Amount') }}</label>
                        <input type="number" step="0.01" min="0.01" name="amount" id="amount"
                            class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" required>
                        @error('amount')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="paid_at">{{ __('Paid At') }}</label>
                        <input type="datetime-local" name="paid_at" id="paid_at"
                            class="form-control @error('paid_at') is-invalid @enderror"
                            value="{{ old('paid_at', now()->format('Y-m-d\TH:i')) }}" required>
                        @error('paid_at')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="reference_no">{{ __('Reference No') }}</label>
                        <input type="text" name="reference_no" id="reference_no"
                            class="form-control @error('reference_no') is-invalid @enderror" value="{{ old('reference_no') }}">
                        @error('reference_no')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label" for="notes">{{ __('Notes') }}</label>
                        <textarea name="notes" id="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                        @error('notes')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">{{ __('Create') }}</button>
            </form>
        </div>
    </div>
@endsection

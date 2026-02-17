@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Purchases')],
            ['label' => __('Purchase Returns'), 'url' => route('tenant.purchase-returns.index')],
            ['label' => __('Create')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Create Purchase Return') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.purchase-returns.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
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

            <form method="POST" action="{{ route('tenant.purchase-returns.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="return_no">{{ __('Return No') }}</label>
                        <input type="text" name="return_no" id="return_no"
                            class="form-control @error('return_no') is-invalid @enderror" value="{{ old('return_no') }}" required>
                        @error('return_no')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="return_date">{{ __('Return Date') }}</label>
                        <input type="date" name="return_date" id="return_date"
                            class="form-control @error('return_date') is-invalid @enderror"
                            value="{{ old('return_date', now()->toDateString()) }}" required>
                        @error('return_date')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-3 mb-3">
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
                    <div class="col-md-3 mb-3">
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
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="status">{{ __('Status') }}</label>
                        <select name="status" id="status"
                            class="form-select singl-select-2 @error('status') is-invalid @enderror" required>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" @selected(old('status', 'draft') === $status->value)>
                                    {{ ucfirst(str_replace('_', ' ', $status->value)) }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="sub_total">{{ __('Sub Total') }}</label>
                        <input type="number" step="0.01" name="sub_total" id="sub_total"
                            class="form-control @error('sub_total') is-invalid @enderror" value="{{ old('sub_total', '0') }}">
                        @error('sub_total')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="tax_total">{{ __('Tax') }}</label>
                        <input type="number" step="0.01" name="tax_total" id="tax_total"
                            class="form-control @error('tax_total') is-invalid @enderror" value="{{ old('tax_total', '0') }}">
                        @error('tax_total')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="grand_total">{{ __('Grand Total') }}</label>
                        <input type="number" step="0.01" name="grand_total" id="grand_total"
                            class="form-control @error('grand_total') is-invalid @enderror" value="{{ old('grand_total', '0') }}">
                        @error('grand_total')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="posted_at">{{ __('Posted At') }}</label>
                        <input type="datetime-local" name="posted_at" id="posted_at"
                            class="form-control @error('posted_at') is-invalid @enderror" value="{{ old('posted_at') }}">
                        @error('posted_at')
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

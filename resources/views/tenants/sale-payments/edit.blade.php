@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Sales')],
            ['label' => __('Sale Payments'), 'url' => route('tenant.sale-payments.index')],
            ['label' => __('Edit')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Edit Sale Payment') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.sale-payments.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
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

            <form method="POST" action="{{ route('tenant.sale-payments.update', $salePayment) }}">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="sale_id">{{ __('Invoice') }}</label>
                        <select name="sale_id" id="sale_id"
                            class="form-select singl-select-2 @error('sale_id') is-invalid @enderror" required>
                            <option value="">{{ __('Select invoice') }}</option>
                            @foreach ($sales as $sale)
                                <option value="{{ $sale->id }}" @selected(old('sale_id', $salePayment->sale_id) === $sale->id)>
                                    {{ $sale->invoice_no }}
                                </option>
                            @endforeach
                        </select>
                        @error('sale_id')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="received_by">{{ __('Received By') }}</label>
                        <select name="received_by" id="received_by"
                            class="form-select singl-select-2 @error('received_by') is-invalid @enderror">
                            <option value="">{{ __('Unassigned') }}</option>
                            @foreach ($receivers as $receiver)
                                <option value="{{ $receiver->id }}" @selected(old('received_by', $salePayment->received_by) === $receiver->id)>
                                    {{ $receiver->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('received_by')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="payment_method">{{ __('Payment Method') }}</label>
                        <select name="payment_method" id="payment_method"
                            class="form-select singl-select-2 @error('payment_method') is-invalid @enderror" required>
                            @foreach ($methods as $method)
                                <option value="{{ $method->value }}" @selected(old('payment_method', $salePayment->payment_method->value) === $method->value)>
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
                            class="form-control @error('amount') is-invalid @enderror"
                            value="{{ old('amount', (string) $salePayment->amount) }}" required>
                        @error('amount')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="paid_at">{{ __('Paid At') }}</label>
                        <input type="datetime-local" name="paid_at" id="paid_at"
                            class="form-control @error('paid_at') is-invalid @enderror"
                            value="{{ old('paid_at', $salePayment->paid_at?->format('Y-m-d\TH:i')) }}" required>
                        @error('paid_at')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="reference_no">{{ __('Reference No') }}</label>
                        <input type="text" name="reference_no" id="reference_no"
                            class="form-control @error('reference_no') is-invalid @enderror"
                            value="{{ old('reference_no', $salePayment->reference_no) }}">
                        @error('reference_no')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label" for="notes">{{ __('Notes') }}</label>
                        <textarea name="notes" id="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $salePayment->notes) }}</textarea>
                        @error('notes')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
            </form>
        </div>
    </div>
@endsection

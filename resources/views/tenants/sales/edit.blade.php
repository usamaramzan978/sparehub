@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Sales')],
            ['label' => __('Sale Invoices'), 'url' => route('tenant.sales.index')],
            ['label' => __('Edit')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Edit Sale Invoice') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.sales.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
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

            <form method="POST" action="{{ route('tenant.sales.update', $sale) }}">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="invoice_no">{{ __('Invoice No') }}</label>
                        <input type="text" name="invoice_no" id="invoice_no"
                            class="form-control @error('invoice_no') is-invalid @enderror"
                            value="{{ old('invoice_no', $sale->invoice_no) }}" required>
                        @error('invoice_no')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="invoice_date">{{ __('Invoice Date') }}</label>
                        <input type="date" name="invoice_date" id="invoice_date"
                            class="form-control @error('invoice_date') is-invalid @enderror"
                            value="{{ old('invoice_date', $sale->invoice_date?->format('Y-m-d')) }}" required>
                        @error('invoice_date')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="customer_id">{{ __('Customer') }}</label>
                        <select name="customer_id" id="customer_id"
                            class="form-select singl-select-2 @error('customer_id') is-invalid @enderror">
                            <option value="">{{ __('Walk-in') }}</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" @selected(old('customer_id', $sale->customer_id) === $customer->id)>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('customer_id')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="job_card_id">{{ __('Job Card') }}</label>
                        <select name="job_card_id" id="job_card_id"
                            class="form-select singl-select-2 @error('job_card_id') is-invalid @enderror">
                            <option value="">{{ __('None') }}</option>
                            @foreach ($jobCards as $jobCard)
                                <option value="{{ $jobCard->id }}" @selected(old('job_card_id', $sale->job_card_id) === $jobCard->id)>
                                    {{ $jobCard->job_no }}
                                </option>
                            @endforeach
                        </select>
                        @error('job_card_id')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="invoice_type">{{ __('Invoice Type') }}</label>
                        <select name="invoice_type" id="invoice_type"
                            class="form-select singl-select-2 @error('invoice_type') is-invalid @enderror" required>
                            @foreach ($invoiceTypes as $type)
                                <option value="{{ $type->value }}" @selected(old('invoice_type', $sale->invoice_type->value) === $type->value)>
                                    {{ ucfirst($type->value) }}
                                </option>
                            @endforeach
                        </select>
                        @error('invoice_type')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="status">{{ __('Status') }}</label>
                        <select name="status" id="status"
                            class="form-select singl-select-2 @error('status') is-invalid @enderror" required>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" @selected(old('status', $sale->status->value) === $status->value)>
                                    {{ ucfirst(str_replace('_', ' ', $status->value)) }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label" for="sub_total">{{ __('Sub Total') }}</label>
                        <input type="number" step="0.01" name="sub_total" id="sub_total"
                            class="form-control @error('sub_total') is-invalid @enderror"
                            value="{{ old('sub_total', (string) $sale->sub_total) }}">
                        @error('sub_total')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label" for="discount_total">{{ __('Discount') }}</label>
                        <input type="number" step="0.01" name="discount_total" id="discount_total"
                            class="form-control @error('discount_total') is-invalid @enderror"
                            value="{{ old('discount_total', (string) $sale->discount_total) }}">
                        @error('discount_total')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label" for="tax_total">{{ __('Tax') }}</label>
                        <input type="number" step="0.01" name="tax_total" id="tax_total"
                            class="form-control @error('tax_total') is-invalid @enderror"
                            value="{{ old('tax_total', (string) $sale->tax_total) }}">
                        @error('tax_total')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label" for="grand_total">{{ __('Grand Total') }}</label>
                        <input type="number" step="0.01" name="grand_total" id="grand_total"
                            class="form-control @error('grand_total') is-invalid @enderror"
                            value="{{ old('grand_total', (string) $sale->grand_total) }}">
                        @error('grand_total')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label" for="paid_total">{{ __('Paid') }}</label>
                        <input type="number" step="0.01" name="paid_total" id="paid_total"
                            class="form-control @error('paid_total') is-invalid @enderror"
                            value="{{ old('paid_total', (string) $sale->paid_total) }}">
                        @error('paid_total')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label" for="balance_due">{{ __('Balance') }}</label>
                        <input type="number" step="0.01" name="balance_due" id="balance_due"
                            class="form-control @error('balance_due') is-invalid @enderror"
                            value="{{ old('balance_due', (string) $sale->balance_due) }}">
                        @error('balance_due')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="posted_at">{{ __('Posted At') }}</label>
                        <input type="datetime-local" name="posted_at" id="posted_at"
                            class="form-control @error('posted_at') is-invalid @enderror"
                            value="{{ old('posted_at', $sale->posted_at?->format('Y-m-d\TH:i')) }}">
                        @error('posted_at')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label" for="notes">{{ __('Notes') }}</label>
                        <textarea name="notes" id="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $sale->notes) }}</textarea>
                        @error('notes')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <button class="btn btn-primary" type="submit">{{ __('Update') }}</button>
            </form>
        </div>
    </div>
@endsection

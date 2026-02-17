@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Purchases')],
            ['label' => __('Purchase Orders'), 'url' => route('tenant.purchases.index')],
            ['label' => __('Create')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Create Purchase') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.purchases.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
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

            <form method="POST" action="{{ route('tenant.purchases.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="purchase_no">{{ __('Purchase No') }}</label>
                        <input type="text" name="purchase_no" id="purchase_no"
                            class="form-control @error('purchase_no') is-invalid @enderror" value="{{ old('purchase_no') }}"
                            required>
                        @error('purchase_no')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="purchase_date">{{ __('Purchase Date') }}</label>
                        <input type="date" name="purchase_date" id="purchase_date"
                            class="form-control @error('purchase_date') is-invalid @enderror"
                            value="{{ old('purchase_date', now()->toDateString()) }}" required>
                        @error('purchase_date')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="due_date">{{ __('Due Date') }}</label>
                        <input type="date" name="due_date" id="due_date"
                            class="form-control @error('due_date') is-invalid @enderror" value="{{ old('due_date') }}">
                        @error('due_date')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="vendor_invoice_no">{{ __('Vendor Invoice No') }}</label>
                        <input type="text" name="vendor_invoice_no" id="vendor_invoice_no"
                            class="form-control @error('vendor_invoice_no') is-invalid @enderror"
                            value="{{ old('vendor_invoice_no') }}">
                        @error('vendor_invoice_no')
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
                        <label class="form-label" for="warehouse_id">{{ __('Warehouse') }}</label>
                        <select name="warehouse_id" id="warehouse_id"
                            class="form-select singl-select-2 @error('warehouse_id') is-invalid @enderror">
                            <option value="">{{ __('None') }}</option>
                            @foreach ($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" @selected(old('warehouse_id') === $warehouse->id)>
                                    {{ $warehouse->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('warehouse_id')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
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
                    <div class="col-md-2 mb-3">
                        <label class="form-label" for="sub_total">{{ __('Sub Total') }}</label>
                        <input type="number" step="0.01" name="sub_total" id="sub_total"
                            class="form-control @error('sub_total') is-invalid @enderror" value="{{ old('sub_total', '0') }}">
                        @error('sub_total')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label" for="discount_total">{{ __('Discount') }}</label>
                        <input type="number" step="0.01" name="discount_total" id="discount_total"
                            class="form-control @error('discount_total') is-invalid @enderror"
                            value="{{ old('discount_total', '0') }}">
                        @error('discount_total')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label" for="tax_total">{{ __('Tax') }}</label>
                        <input type="number" step="0.01" name="tax_total" id="tax_total"
                            class="form-control @error('tax_total') is-invalid @enderror" value="{{ old('tax_total', '0') }}">
                        @error('tax_total')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label" for="shipping_total">{{ __('Shipping') }}</label>
                        <input type="number" step="0.01" name="shipping_total" id="shipping_total"
                            class="form-control @error('shipping_total') is-invalid @enderror"
                            value="{{ old('shipping_total', '0') }}">
                        @error('shipping_total')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label" for="grand_total">{{ __('Grand Total') }}</label>
                        <input type="number" step="0.01" name="grand_total" id="grand_total"
                            class="form-control @error('grand_total') is-invalid @enderror" value="{{ old('grand_total', '0') }}">
                        @error('grand_total')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label" for="paid_total">{{ __('Paid') }}</label>
                        <input type="number" step="0.01" name="paid_total" id="paid_total"
                            class="form-control @error('paid_total') is-invalid @enderror" value="{{ old('paid_total', '0') }}">
                        @error('paid_total')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label" for="balance_due">{{ __('Balance') }}</label>
                        <input type="number" step="0.01" name="balance_due" id="balance_due"
                            class="form-control @error('balance_due') is-invalid @enderror" value="{{ old('balance_due', '0') }}">
                        @error('balance_due')
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

                <button class="btn btn-primary" type="submit">{{ __('Create') }}</button>
            </form>
        </div>
    </div>
@endsection

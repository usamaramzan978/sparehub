@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Purchases')],
            ['label' => __('Purchase Return Items'), 'url' => route('tenant.purchase-return-items.index')],
            ['label' => __('Create')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Create Purchase Return Item') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.purchase-return-items.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
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

            <form method="POST" action="{{ route('tenant.purchase-return-items.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="purchase_return_id">{{ __('Purchase Return') }}</label>
                        <select name="purchase_return_id" id="purchase_return_id"
                            class="form-select singl-select-2 @error('purchase_return_id') is-invalid @enderror" required>
                            <option value="">{{ __('Select return') }}</option>
                            @foreach ($purchaseReturns as $purchaseReturn)
                                <option value="{{ $purchaseReturn->id }}" @selected(old('purchase_return_id') === $purchaseReturn->id)>
                                    {{ $purchaseReturn->return_no }}
                                </option>
                            @endforeach
                        </select>
                        @error('purchase_return_id')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="purchase_item_id">{{ __('Purchase Item') }}</label>
                        <select name="purchase_item_id" id="purchase_item_id"
                            class="form-select singl-select-2 @error('purchase_item_id') is-invalid @enderror">
                            <option value="">{{ __('None') }}</option>
                            @foreach ($purchaseItems as $purchaseItem)
                                <option value="{{ $purchaseItem->id }}" @selected(old('purchase_item_id') === $purchaseItem->id)>
                                    {{ $purchaseItem->purchase?->purchase_no ?? '-' }} - {{ $purchaseItem->product?->name ?? '-' }}
                                </option>
                            @endforeach
                        </select>
                        @error('purchase_item_id')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="product_id">{{ __('Product') }}</label>
                        <select name="product_id" id="product_id"
                            class="form-select singl-select-2 @error('product_id') is-invalid @enderror" required>
                            <option value="">{{ __('Select product') }}</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" @selected(old('product_id') === $product->id)>
                                    {{ $product->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('product_id')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="tax_id">{{ __('Tax') }}</label>
                        <select name="tax_id" id="tax_id"
                            class="form-select singl-select-2 @error('tax_id') is-invalid @enderror">
                            <option value="">{{ __('None') }}</option>
                            @foreach ($taxes as $tax)
                                <option value="{{ $tax->id }}" @selected(old('tax_id') === $tax->id)>
                                    {{ $tax->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('tax_id')
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
                        <label class="form-label" for="unit_cost">{{ __('Unit Cost') }}</label>
                        <input type="number" step="0.01" min="0" name="unit_cost" id="unit_cost"
                            class="form-control @error('unit_cost') is-invalid @enderror"
                            value="{{ old('unit_cost', '0') }}" required>
                        @error('unit_cost')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label" for="tax_amount">{{ __('Tax Amount') }}</label>
                        <input type="number" step="0.01" min="0" name="tax_amount" id="tax_amount"
                            class="form-control @error('tax_amount') is-invalid @enderror"
                            value="{{ old('tax_amount', '0') }}">
                        @error('tax_amount')
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

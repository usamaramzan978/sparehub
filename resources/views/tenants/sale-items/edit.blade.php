@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Sales')],
            ['label' => __('Sale Items'), 'url' => route('tenant.sale-items.index')],
            ['label' => __('Edit')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Edit Sale Item') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.sale-items.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
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

            <form method="POST" action="{{ route('tenant.sale-items.update', $saleItem) }}">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="sale_id">{{ __('Invoice') }}</label>
                        <select name="sale_id" id="sale_id"
                            class="form-select singl-select-2 @error('sale_id') is-invalid @enderror" required>
                            <option value="">{{ __('Select invoice') }}</option>
                            @foreach ($sales as $sale)
                                <option value="{{ $sale->id }}" @selected(old('sale_id', $saleItem->sale_id) === $sale->id)>
                                    {{ $sale->invoice_no }}
                                </option>
                            @endforeach
                        </select>
                        @error('sale_id')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-2 mb-3">
                        <label class="form-label" for="line_type">{{ __('Line Type') }}</label>
                        <select name="line_type" id="line_type"
                            class="form-select singl-select-2 @error('line_type') is-invalid @enderror" required>
                            @foreach ($lineTypes as $lineType)
                                <option value="{{ $lineType->value }}" @selected(old('line_type', $saleItem->line_type->value) === $lineType->value)>
                                    {{ ucfirst($lineType->value) }}
                                </option>
                            @endforeach
                        </select>
                        @error('line_type')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="product_id">{{ __('Product') }}</label>
                        <select name="product_id" id="product_id"
                            class="form-select singl-select-2 @error('product_id') is-invalid @enderror">
                            <option value="">{{ __('None') }}</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" @selected(old('product_id', $saleItem->product_id) === $product->id)>
                                    {{ $product->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('product_id')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="service_catalog_id">{{ __('Service Catalog') }}</label>
                        <select name="service_catalog_id" id="service_catalog_id"
                            class="form-select singl-select-2 @error('service_catalog_id') is-invalid @enderror">
                            <option value="">{{ __('None') }}</option>
                            @foreach ($serviceCatalogs as $serviceCatalog)
                                <option value="{{ $serviceCatalog->id }}" @selected(old('service_catalog_id', $saleItem->service_catalog_id) === $serviceCatalog->id)>
                                    {{ $serviceCatalog->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('service_catalog_id')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="job_card_service_id">{{ __('Job Card Service') }}</label>
                        <select name="job_card_service_id" id="job_card_service_id"
                            class="form-select singl-select-2 @error('job_card_service_id') is-invalid @enderror">
                            <option value="">{{ __('None') }}</option>
                            @foreach ($jobCardServices as $jobCardService)
                                <option value="{{ $jobCardService->id }}" @selected(old('job_card_service_id', $saleItem->job_card_service_id) === $jobCardService->id)>
                                    {{ $jobCardService->service_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('job_card_service_id')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-8 mb-3">
                        <label class="form-label" for="description">{{ __('Description') }}</label>
                        <input type="text" name="description" id="description"
                            class="form-control @error('description') is-invalid @enderror"
                            value="{{ old('description', $saleItem->description) }}">
                        @error('description')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="mechanic_id">{{ __('Mechanic') }}</label>
                        <select name="mechanic_id" id="mechanic_id"
                            class="form-select singl-select-2 @error('mechanic_id') is-invalid @enderror">
                            <option value="">{{ __('None') }}</option>
                            @foreach ($mechanics as $mechanic)
                                <option value="{{ $mechanic->id }}" @selected(old('mechanic_id', $saleItem->mechanic_id) === $mechanic->id)>
                                    {{ $mechanic->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('mechanic_id')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-2 mb-3">
                        <label class="form-label" for="qty">{{ __('Qty') }}</label>
                        <input type="number" step="0.001" min="0.001" name="qty" id="qty"
                            class="form-control @error('qty') is-invalid @enderror"
                            value="{{ old('qty', (string) $saleItem->qty) }}" required>
                        @error('qty')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-2 mb-3">
                        <label class="form-label" for="unit_price">{{ __('Unit Price') }}</label>
                        <input type="number" step="0.01" min="0" name="unit_price" id="unit_price"
                            class="form-control @error('unit_price') is-invalid @enderror"
                            value="{{ old('unit_price', (string) $saleItem->unit_price) }}" required>
                        @error('unit_price')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-2 mb-3">
                        <label class="form-label" for="discount_amount">{{ __('Discount') }}</label>
                        <input type="number" step="0.01" min="0" name="discount_amount" id="discount_amount"
                            class="form-control @error('discount_amount') is-invalid @enderror"
                            value="{{ old('discount_amount', (string) $saleItem->discount_amount) }}">
                        @error('discount_amount')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-2 mb-3">
                        <label class="form-label" for="tax_amount">{{ __('Tax') }}</label>
                        <input type="number" step="0.01" min="0" name="tax_amount" id="tax_amount"
                            class="form-control @error('tax_amount') is-invalid @enderror"
                            value="{{ old('tax_amount', (string) $saleItem->tax_amount) }}">
                        @error('tax_amount')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label" for="mechanic_charge">{{ __('Mechanic Payable') }}</label>
                        <input type="number" step="0.01" min="0" name="mechanic_charge" id="mechanic_charge"
                            class="form-control @error('mechanic_charge') is-invalid @enderror"
                            value="{{ old('mechanic_charge', (string) $saleItem->mechanic_charge) }}">
                        @error('mechanic_charge')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
            </form>
        </div>
    </div>
@endsection

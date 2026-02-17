@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Catalog')],
            ['label' => __('Products'), 'url' => route('tenant.products.index')],
            ['label' => __('Create')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Create Product') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.products.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
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

            <form method="POST" action="{{ route('tenant.products.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="sku">{{ __('SKU') }}</label>
                        <input type="text" name="sku" id="sku" class="form-control @error('sku') is-invalid @enderror"
                            value="{{ old('sku') }}" required>
                        @error('sku')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-5 mb-3">
                        <label class="form-label" for="name">{{ __('Name') }}</label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name') }}" required>
                        @error('name')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="status">{{ __('Status') }}</label>
                        <select name="status" id="status" class="form-select singl-select-2 @error('status') is-invalid @enderror"
                            required>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" @selected(old('status', 'active') === $status->value)>{{ ucfirst($status->value) }}</option>
                            @endforeach
                        </select>
                        @error('status')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="part_number">{{ __('Part Number') }}</label>
                        <input type="text" name="part_number" id="part_number"
                            class="form-control @error('part_number') is-invalid @enderror" value="{{ old('part_number') }}">
                        @error('part_number')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="barcode">{{ __('Barcode') }}</label>
                        <input type="text" name="barcode" id="barcode"
                            class="form-control @error('barcode') is-invalid @enderror" value="{{ old('barcode') }}">
                        @error('barcode')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="default_unit_id">{{ __('Default Unit') }}</label>
                        <select name="default_unit_id" id="default_unit_id"
                            class="form-select singl-select-2 @error('default_unit_id') is-invalid @enderror">
                            <option value="">{{ __('None') }}</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}" @selected(old('default_unit_id') === $unit->id)>{{ $unit->name }}</option>
                            @endforeach
                        </select>
                        @error('default_unit_id')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="category_id">{{ __('Category') }}</label>
                        <select name="category_id" id="category_id"
                            class="form-select singl-select-2 @error('category_id') is-invalid @enderror">
                            <option value="">{{ __('None') }}</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id') === $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="brand_id">{{ __('Brand') }}</label>
                        <select name="brand_id" id="brand_id"
                            class="form-select singl-select-2 @error('brand_id') is-invalid @enderror">
                            <option value="">{{ __('None') }}</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}" @selected(old('brand_id') === $brand->id)>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                        @error('brand_id')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="default_tax_id">{{ __('Default Tax') }}</label>
                        <select name="default_tax_id" id="default_tax_id"
                            class="form-select singl-select-2 @error('default_tax_id') is-invalid @enderror">
                            <option value="">{{ __('None') }}</option>
                            @foreach ($taxes as $tax)
                                <option value="{{ $tax->id }}" @selected(old('default_tax_id') === $tax->id)>{{ $tax->name }}
                                    ({{ $tax->rate }}%)</option>
                            @endforeach
                        </select>
                        @error('default_tax_id')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3 form-check">
                        <input type="hidden" name="track_stock" value="0">
                        <input type="checkbox" name="track_stock" id="track_stock"
                            class="form-check-input @error('track_stock') is-invalid @enderror" value="1"
                            @checked(old('track_stock', true))>
                        <label class="form-check-label" for="track_stock">{{ __('Track Stock') }}</label>
                        @error('track_stock')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3 form-check">
                        <input type="hidden" name="is_service_item" value="0">
                        <input type="checkbox" name="is_service_item" id="is_service_item"
                            class="form-check-input @error('is_service_item') is-invalid @enderror" value="1"
                            @checked(old('is_service_item', false))>
                        <label class="form-check-label" for="is_service_item">{{ __('Service Item') }}</label>
                        @error('is_service_item')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label" for="description">{{ __('Description') }}</label>
                        <textarea name="description" id="description"
                            class="form-control @error('description') is-invalid @enderror" rows="4">{{ old('description') }}</textarea>
                        @error('description')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">{{ __('Create') }}</button>
            </form>
        </div>
    </div>
@endsection

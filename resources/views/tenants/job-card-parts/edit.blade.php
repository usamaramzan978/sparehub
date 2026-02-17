@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Workshop')],
            ['label' => __('Job Card Parts'), 'url' => route('tenant.job-card-parts.index')],
            ['label' => __('Edit')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Edit Part Line') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.job-card-parts.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
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

            <form method="POST" action="{{ route('tenant.job-card-parts.update', $partLine) }}">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="job_card_id">{{ __('Job Card') }}</label>
                        <select name="job_card_id" id="job_card_id"
                            class="form-select singl-select-2 @error('job_card_id') is-invalid @enderror" required>
                            <option value="">{{ __('Select job card') }}</option>
                            @foreach ($jobCards as $jobCard)
                                <option value="{{ $jobCard->id }}" @selected(old('job_card_id', $partLine->job_card_id) === $jobCard->id)>
                                    {{ $jobCard->job_no }} - {{ $jobCard->customer?->name ?? '-' }}
                                </option>
                            @endforeach
                        </select>
                        @error('job_card_id')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="product_id">{{ __('Product') }}</label>
                        <select name="product_id" id="product_id"
                            class="form-select singl-select-2 @error('product_id') is-invalid @enderror" required>
                            <option value="">{{ __('Select product') }}</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" @selected(old('product_id', $partLine->product_id) === $product->id)>
                                    {{ $product->name }} ({{ $product->sku }})
                                </option>
                            @endforeach
                        </select>
                        @error('product_id')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="qty">{{ __('Qty') }}</label>
                        <input type="number" step="0.001" min="0.001" name="qty" id="qty"
                            class="form-control @error('qty') is-invalid @enderror"
                            value="{{ old('qty', (string) $partLine->qty) }}" required>
                        @error('qty')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="unit_price">{{ __('Unit Price') }}</label>
                        <input type="number" step="0.01" min="0" name="unit_price" id="unit_price"
                            class="form-control @error('unit_price') is-invalid @enderror"
                            value="{{ old('unit_price', (string) $partLine->unit_price) }}" required>
                        @error('unit_price')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
            </form>
        </div>
    </div>
@endsection

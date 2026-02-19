@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [['label' => __('Catalog')], ['label' => __('Products')], ['label' => __('Stock Adjustment')]];
    @endphp

    <x-breadcrumb title="{{ __('Stock Adjustment') }}" :items="$breadcrumbs" />

    @push('styles')
        <style>
            .stock-left-badge {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 2.25rem;
                padding: 0.15rem 0.5rem;
                border-radius: 999px;
                font-size: 0.75rem;
                font-weight: 600;
            }
        </style>
    @endpush

    <div class="card mb-3">
        <div class="card-body">
            <form method="POST" action="{{ route('tenant.products.stock.adjustments.store') }}" class="row g-3">
                @csrf
                <div class="col-12 col-lg-4">
                    <label class="form-label" for="adjust_product_id">{{ __('Product') }}</label>
                    <select class="form-select singl-select-2 js-stock-product-select @error('product_id') is-invalid @enderror"
                        id="adjust_product_id" name="product_id" required>
                        <option value="">{{ __('Select Product') }}</option>
                        @foreach ($adjustableProducts as $adjustableProduct)
                            <option value="{{ $adjustableProduct->id }}" data-stock="{{ (float) $adjustableProduct->qty_available }}"
                                data-opening="{{ (float) $adjustableProduct->opening_stock }}"
                                @selected(old('product_id') === $adjustableProduct->id)>
                                {{ $adjustableProduct->name }} ({{ $adjustableProduct->sku }})
                            </option>
                        @endforeach
                    </select>
                    @error('product_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12 col-lg-4">
                    <label class="form-label" for="adjust_action">{{ __('Action') }}</label>
                    <select class="form-select singl-select-2 @error('action') is-invalid @enderror" id="adjust_action"
                        name="action" required>
                        <option value="in" @selected(old('action', 'in') === 'in')>{{ __('Add') }}</option>
                        <option value="out" @selected(old('action') === 'out')>{{ __('Remove') }}</option>
                        <option value="set" @selected(old('action') === 'set')>{{ __('Set Exact') }}</option>
                    </select>
                    @error('action')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12 col-lg-4">
                    <label class="form-label" for="adjust_qty">{{ __('Quantity') }}</label>
                    <input type="number" step="0.001" min="0"
                        class="form-control @error('qty') is-invalid @enderror" id="adjust_qty" name="qty"
                        value="{{ old('qty') }}" required>
                    @error('qty')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12">
                    <label class="form-label" for="adjust_remarks">{{ __('Remarks') }}</label>
                    <input type="text" class="form-control @error('remarks') is-invalid @enderror" id="adjust_remarks"
                        name="remarks" value="{{ old('remarks') }}" maxlength="500"
                        placeholder="{{ __('Optional note for this adjustment') }}">
                    @error('remarks')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-primary" type="submit">{{ __('Apply Adjustment') }}</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            (() => {
                const renderOption = (state) => {
                    if (!state.id) {
                        return state.text;
                    }

                    const stockRaw = state.element?.dataset?.stock ?? '0';
                    const openingRaw = state.element?.dataset?.opening ?? '0';
                    const stock = Number.parseFloat(stockRaw);
                    const opening = Number.parseFloat(openingRaw);
                    const safeStock = Number.isFinite(stock) ? stock : 0;
                    const safeOpening = Number.isFinite(opening) ? opening : 0;
                    const badgeValue = `${safeStock.toFixed(0)}/${safeOpening.toFixed(0)}`;

                    return `
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <span>${state.text}</span>
                            <span class="badge bg-info-transparent stock-left-badge">${badgeValue}</span>
                        </div>
                    `;
                };

                const initStockSelect2 = (attempt = 0) => {
                    if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) {
                        if (attempt < 20) {
                            window.setTimeout(() => initStockSelect2(attempt + 1), 100);
                        }
                        return;
                    }

                    const $select = window.jQuery('.js-stock-product-select');
                    if (! $select.length) {
                        return;
                    }

                    if ($select.hasClass('select2-hidden-accessible')) {
                        $select.select2('destroy');
                    }

                    $select.select2({
                        width: '100%',
                        templateResult: renderOption,
                        templateSelection: renderOption,
                        escapeMarkup: (markup) => markup,
                    });
                };

                document.addEventListener('DOMContentLoaded', () => initStockSelect2());
                window.addEventListener('load', () => initStockSelect2());
            })();
        </script>
    @endpush
@endsection

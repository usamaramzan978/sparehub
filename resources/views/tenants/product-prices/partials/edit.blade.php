<div class="modal fade" id="productPriceEditModal-{{ $productPrice->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Edit Product Price') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ route('tenant.product-prices.update', $productPrice) }}">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label" for="product_id_{{ $productPrice->id }}">{{ __('Product') }}</label>
                            <select name="product_id" id="product_id_{{ $productPrice->id }}"
                                class="form-select singl-select-2 @error('product_id') is-invalid @enderror" required>
                                <option value="">{{ __('Select product') }}</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}"
                                        @selected(old('product_id', $productPrice->product_id) === $product->id)>
                                        {{ $product->name }} ({{ $product->sku }})
                                    </option>
                                @endforeach
                            </select>
                            @error('product_id')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label" for="cost_{{ $productPrice->id }}">{{ __('Cost') }}</label>
                            <input type="number" step="0.01" min="0" name="cost" id="cost_{{ $productPrice->id }}"
                                class="form-control @error('cost') is-invalid @enderror"
                                value="{{ old('cost', (string) $productPrice->cost) }}" required>
                            @error('cost')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label" for="mrp_{{ $productPrice->id }}">{{ __('MRP') }}</label>
                            <input type="number" step="0.01" min="0" name="mrp" id="mrp_{{ $productPrice->id }}"
                                class="form-control @error('mrp') is-invalid @enderror"
                                value="{{ old('mrp', (string) $productPrice->mrp) }}" required>
                            @error('mrp')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label" for="retail_price_{{ $productPrice->id }}">{{ __('Retail') }}</label>
                            <input type="number" step="0.01" min="0" name="retail_price"
                                id="retail_price_{{ $productPrice->id }}"
                                class="form-control @error('retail_price') is-invalid @enderror"
                                value="{{ old('retail_price', (string) $productPrice->retail_price) }}" required>
                            @error('retail_price')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label" for="wholesale_price_{{ $productPrice->id }}">{{ __('Wholesale') }}</label>
                            <input type="number" step="0.01" min="0" name="wholesale_price"
                                id="wholesale_price_{{ $productPrice->id }}"
                                class="form-control @error('wholesale_price') is-invalid @enderror"
                                value="{{ old('wholesale_price', (string) $productPrice->wholesale_price) }}" required>
                            @error('wholesale_price')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label" for="effective_from_{{ $productPrice->id }}">{{ __('Effective From') }}</label>
                            <input type="datetime-local" name="effective_from" id="effective_from_{{ $productPrice->id }}"
                                class="form-control @error('effective_from') is-invalid @enderror"
                                value="{{ old('effective_from', $productPrice->effective_from?->format('Y-m-d\TH:i')) }}"
                                required>
                            @error('effective_from')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

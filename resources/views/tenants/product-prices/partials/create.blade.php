<div class="modal fade" id="productPriceCreateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Add Product Price') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ route('tenant.product-prices.store') }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label" for="product_id">{{ __('Product') }}</label>
                            <select name="product_id" id="product_id"
                                class="form-select singl-select-2 @error('product_id') is-invalid @enderror" required>
                                <option value="">{{ __('Select product') }}</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" @selected(old('product_id') === $product->id)>
                                        {{ $product->name }} ({{ $product->sku }})
                                    </option>
                                @endforeach
                            </select>
                            @error('product_id')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label" for="cost">{{ __('Cost') }}</label>
                            <input type="number" step="0.01" min="0" name="cost" id="cost"
                                class="form-control @error('cost') is-invalid @enderror"
                                value="{{ old('cost', '0') }}" required>
                            @error('cost')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label" for="mrp">{{ __('MRP') }}</label>
                            <input type="number" step="0.01" min="0" name="mrp" id="mrp"
                                class="form-control @error('mrp') is-invalid @enderror"
                                value="{{ old('mrp', '0') }}" required>
                            @error('mrp')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label" for="retail_price">{{ __('Retail') }}</label>
                            <input type="number" step="0.01" min="0" name="retail_price" id="retail_price"
                                class="form-control @error('retail_price') is-invalid @enderror"
                                value="{{ old('retail_price', '0') }}" required>
                            @error('retail_price')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label" for="wholesale_price">{{ __('Wholesale') }}</label>
                            <input type="number" step="0.01" min="0" name="wholesale_price" id="wholesale_price"
                                class="form-control @error('wholesale_price') is-invalid @enderror"
                                value="{{ old('wholesale_price', '0') }}" required>
                            @error('wholesale_price')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label" for="effective_from">{{ __('Effective From') }}</label>
                            <input type="datetime-local" name="effective_from" id="effective_from"
                                class="form-control @error('effective_from') is-invalid @enderror"
                                value="{{ old('effective_from', now()->format('Y-m-d\TH:i')) }}" required>
                            @error('effective_from')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Create') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

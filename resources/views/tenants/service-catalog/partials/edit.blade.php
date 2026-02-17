<div class="modal fade" id="serviceCatalogEditModal-{{ $serviceCatalog->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Edit Service') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ route('tenant.service-catalog.update', $serviceCatalog) }}">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label" for="code_{{ $serviceCatalog->id }}">{{ __('Code') }}</label>
                            <input type="text" name="code" id="code_{{ $serviceCatalog->id }}"
                                class="form-control @error('code') is-invalid @enderror"
                                value="{{ old('code', $serviceCatalog->code) }}" required>
                            @error('code')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label" for="name_{{ $serviceCatalog->id }}">{{ __('Name') }}</label>
                            <input type="text" name="name" id="name_{{ $serviceCatalog->id }}"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $serviceCatalog->name) }}" required>
                            @error('name')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label" for="category_{{ $serviceCatalog->id }}">{{ __('Category') }}</label>
                            <input type="text" name="category" id="category_{{ $serviceCatalog->id }}"
                                class="form-control @error('category') is-invalid @enderror"
                                value="{{ old('category', $serviceCatalog->category) }}">
                            @error('category')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label" for="base_price_{{ $serviceCatalog->id }}">{{ __('Base Price') }}</label>
                            <input type="number" step="0.01" min="0" name="base_price"
                                id="base_price_{{ $serviceCatalog->id }}"
                                class="form-control @error('base_price') is-invalid @enderror"
                                value="{{ old('base_price', (string) $serviceCatalog->base_price) }}" required>
                            @error('base_price')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label" for="duration_minutes_{{ $serviceCatalog->id }}">{{ __('Duration (Min)') }}</label>
                            <input type="number" min="1" name="duration_minutes"
                                id="duration_minutes_{{ $serviceCatalog->id }}"
                                class="form-control @error('duration_minutes') is-invalid @enderror"
                                value="{{ old('duration_minutes', $serviceCatalog->duration_minutes) }}">
                            @error('duration_minutes')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label" for="default_tax_id_{{ $serviceCatalog->id }}">{{ __('Default Tax') }}</label>
                            <select name="default_tax_id" id="default_tax_id_{{ $serviceCatalog->id }}"
                                class="form-select singl-select-2 @error('default_tax_id') is-invalid @enderror">
                                <option value="">{{ __('No tax') }}</option>
                                @foreach ($taxes as $tax)
                                    <option value="{{ $tax->id }}"
                                        @selected(old('default_tax_id', $serviceCatalog->default_tax_id) === $tax->id)>
                                        {{ $tax->name }} ({{ $tax->rate }}%)
                                    </option>
                                @endforeach
                            </select>
                            @error('default_tax_id')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label" for="status_{{ $serviceCatalog->id }}">{{ __('Status') }}</label>
                            <select name="status" id="status_{{ $serviceCatalog->id }}"
                                class="form-select singl-select-2 @error('status') is-invalid @enderror" required>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->value }}"
                                        @selected(old('status', $serviceCatalog->status->value) === $status->value)>
                                        {{ ucfirst($status->value) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-12 mb-3">
                            <div class="form-check">
                                <input type="hidden" name="is_taxable" value="0">
                                <input class="form-check-input" type="checkbox" value="1"
                                    id="is_taxable_{{ $serviceCatalog->id }}" name="is_taxable"
                                    @checked((bool) old('is_taxable', $serviceCatalog->is_taxable))>
                                <label class="form-check-label" for="is_taxable_{{ $serviceCatalog->id }}">{{ __('Taxable Service') }}</label>
                            </div>
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

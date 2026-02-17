<div class="modal fade" id="taxEditModal-{{ $tax->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Edit Tax') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ route('tenant.taxes.update', $tax) }}">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label" for="code_{{ $tax->id }}">{{ __('Code') }}</label>
                            <input type="text" name="code" id="code_{{ $tax->id }}"
                                class="form-control @error('code') is-invalid @enderror"
                                value="{{ old('code', $tax->code) }}" required>
                            @error('code')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label" for="name_{{ $tax->id }}">{{ __('Name') }}</label>
                            <input type="text" name="name" id="name_{{ $tax->id }}"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $tax->name) }}" required>
                            @error('name')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label" for="rate_{{ $tax->id }}">{{ __('Rate (%)') }}</label>
                            <input type="number" step="0.01" name="rate" id="rate_{{ $tax->id }}"
                                class="form-control @error('rate') is-invalid @enderror"
                                value="{{ old('rate', $tax->rate) }}" required>
                            @error('rate')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label" for="status_{{ $tax->id }}">{{ __('Status') }}</label>
                            <select name="status" id="status_{{ $tax->id }}"
                                class="form-select singl-select-2 @error('status') is-invalid @enderror" required>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->value }}"
                                        @selected(old('status', $tax->status->value) === $status->value)>
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
                                <input class="form-check-input" type="checkbox" value="1"
                                    id="is_inclusive_{{ $tax->id }}" name="is_inclusive"
                                    @checked((bool) old('is_inclusive', $tax->is_inclusive))>
                                <label class="form-check-label" for="is_inclusive_{{ $tax->id }}">
                                    {{ __('Inclusive Tax') }}
                                </label>
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

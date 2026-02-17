<div class="modal fade" id="unitEditModal-{{ $unit->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Edit Unit') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ route('tenant.units.update', $unit) }}">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label" for="unit-code-{{ $unit->id }}">{{ __('Code') }}</label>
                            <input type="text" name="code" id="unit-code-{{ $unit->id }}"
                                class="form-control @error('code') is-invalid @enderror"
                                value="{{ old('code', $unit->code) }}" required>
                            @error('code')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label" for="unit-name-{{ $unit->id }}">{{ __('Name') }}</label>
                            <input type="text" name="name" id="unit-name-{{ $unit->id }}"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $unit->name) }}" required>
                            @error('name')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label" for="unit-status-{{ $unit->id }}">{{ __('Status') }}</label>
                            <select name="status" id="unit-status-{{ $unit->id }}"
                                class="form-select singl-select-2 @error('status') is-invalid @enderror" required>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->value }}"
                                        @selected(old('status', $unit->status->value) === $status->value)>
                                        {{ ucfirst($status->value) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label d-block">{{ __('Fractional') }}</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" value="1"
                                    id="is_fractional_{{ $unit->id }}" name="is_fractional"
                                    @checked((bool) old('is_fractional', $unit->is_fractional))>
                                <label class="form-check-label" for="is_fractional_{{ $unit->id }}">
                                    {{ __('Yes') }}
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

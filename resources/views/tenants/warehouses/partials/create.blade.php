<div class="modal fade" id="warehouseCreateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Add Warehouse') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ route('tenant.warehouses.store') }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label" for="warehouse-code">{{ __('Code') }}</label>
                            <input type="text" name="code" id="warehouse-code"
                                class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}"
                                required>
                            @error('code')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label" for="warehouse-name">{{ __('Name') }}</label>
                            <input type="text" name="name" id="warehouse-name"
                                class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}"
                                required>
                            @error('name')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label" for="warehouse-status">{{ __('Status') }}</label>
                            <select name="status" id="warehouse-status"
                                class="form-select singl-select-2 @error('status') is-invalid @enderror" required>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->value }}" @selected(old('status', 'active') === $status->value)>
                                        {{ ucfirst($status->value) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Create') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

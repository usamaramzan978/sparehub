<div class="modal fade" id="customerVehicleEditModal-{{ $vehicle->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Edit Vehicle') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('tenant.customer-vehicles.update', $vehicle) }}">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="customer_id_{{ $vehicle->id }}">{{ __('Customer') }}</label>
                            <select name="customer_id" id="customer_id_{{ $vehicle->id }}"
                                class="form-select singl-select-2 @error('customer_id') is-invalid @enderror" required>
                                <option value="">{{ __('Select customer') }}</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}"
                                        @selected(old('customer_id', $vehicle->customer_id) === $customer->id)>
                                        {{ $customer->name }} ({{ $customer->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('customer_id')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="registration_no_{{ $vehicle->id }}">{{ __('Registration No') }}</label>
                            <input type="text" name="registration_no" id="registration_no_{{ $vehicle->id }}"
                                class="form-control @error('registration_no') is-invalid @enderror"
                                value="{{ old('registration_no', $vehicle->registration_no) }}" required>
                            @error('registration_no')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label" for="model_{{ $vehicle->id }}">{{ __('Model') }}</label>
                            <input type="text" name="model" id="model_{{ $vehicle->id }}"
                                class="form-control @error('model') is-invalid @enderror"
                                value="{{ old('model', $vehicle->model) }}">
                            @error('model')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label" for="year_{{ $vehicle->id }}">{{ __('Year') }}</label>
                            <input type="number" name="year" id="year_{{ $vehicle->id }}"
                                class="form-control @error('year') is-invalid @enderror"
                                value="{{ old('year', $vehicle->year) }}">
                            @error('year')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label" for="meter_reading_{{ $vehicle->id }}">{{ __('Meter Reading') }}</label>
                            <input type="number" step="0.001" min="0" name="meter_reading" id="meter_reading_{{ $vehicle->id }}"
                                class="form-control @error('meter_reading') is-invalid @enderror"
                                value="{{ old('meter_reading', (string) $vehicle->meter_reading) }}">
                            @error('meter_reading')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="chassis_no_{{ $vehicle->id }}">{{ __('Chassis No') }}</label>
                            <input type="text" name="chassis_no" id="chassis_no_{{ $vehicle->id }}"
                                class="form-control @error('chassis_no') is-invalid @enderror"
                                value="{{ old('chassis_no', $vehicle->chassis_no) }}">
                            @error('chassis_no')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="engine_no_{{ $vehicle->id }}">{{ __('Engine No') }}</label>
                            <input type="text" name="engine_no" id="engine_no_{{ $vehicle->id }}"
                                class="form-control @error('engine_no') is-invalid @enderror"
                                value="{{ old('engine_no', $vehicle->engine_no) }}">
                            @error('engine_no')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

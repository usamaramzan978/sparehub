<div class="modal fade" id="vendorEditModal-{{ $vendor->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Edit Vendor') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('tenant.vendors.update', $vendor) }}">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label" for="code_{{ $vendor->id }}">{{ __('Code') }}</label>
                            <input type="text" name="code" id="code_{{ $vendor->id }}"
                                class="form-control @error('code') is-invalid @enderror"
                                value="{{ old('code', $vendor->code) }}" required>
                            @error('code')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label" for="name_{{ $vendor->id }}">{{ __('Name') }}</label>
                            <input type="text" name="name" id="name_{{ $vendor->id }}"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $vendor->name) }}" required>
                            @error('name')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label" for="phone_{{ $vendor->id }}">{{ __('Phone') }}</label>
                            <input type="text" name="phone" id="phone_{{ $vendor->id }}"
                                class="form-control @error('phone') is-invalid @enderror"
                                value="{{ old('phone', $vendor->phone) }}">
                            @error('phone')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label" for="email_{{ $vendor->id }}">{{ __('Email') }}</label>
                            <input type="email" name="email" id="email_{{ $vendor->id }}"
                                class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email', $vendor->email) }}">
                            @error('email')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label" for="cnic_{{ $vendor->id }}">{{ __('CNIC') }}</label>
                            <input type="text" name="cnic" id="cnic_{{ $vendor->id }}"
                                class="form-control @error('cnic') is-invalid @enderror"
                                value="{{ old('cnic', $vendor->cnic) }}">
                            @error('cnic')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label" for="ntn_{{ $vendor->id }}">{{ __('NTN') }}</label>
                            <input type="text" name="ntn" id="ntn_{{ $vendor->id }}"
                                class="form-control @error('ntn') is-invalid @enderror"
                                value="{{ old('ntn', $vendor->ntn) }}">
                            @error('ntn')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label" for="city_{{ $vendor->id }}">{{ __('City') }}</label>
                            <input type="text" name="city" id="city_{{ $vendor->id }}"
                                class="form-control @error('city') is-invalid @enderror"
                                value="{{ old('city', $vendor->city) }}">
                            @error('city')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label" for="opening_balance_{{ $vendor->id }}">{{ __('Opening Balance') }}</label>
                            <input type="number" step="0.01" name="opening_balance" id="opening_balance_{{ $vendor->id }}"
                                class="form-control @error('opening_balance') is-invalid @enderror"
                                value="{{ old('opening_balance', (string) $vendor->opening_balance) }}">
                            @error('opening_balance')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label" for="status_{{ $vendor->id }}">{{ __('Status') }}</label>
                            <select name="status" id="status_{{ $vendor->id }}"
                                class="form-select singl-select-2 @error('status') is-invalid @enderror" required>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->value }}" @selected(old('status', $vendor->status->value) === $status->value)>
                                        {{ ucfirst($status->value) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label" for="address_{{ $vendor->id }}">{{ __('Address') }}</label>
                            <textarea name="address" id="address_{{ $vendor->id }}" rows="2" class="form-control @error('address') is-invalid @enderror">{{ old('address', $vendor->address) }}</textarea>
                            @error('address')
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

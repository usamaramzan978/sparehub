<div class="modal fade" id="saleHoldEditModal-{{ $saleHold->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Edit Sale Hold') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('tenant.sale-holds.update', $saleHold) }}">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="hold_no_{{ $saleHold->id }}">{{ __('Hold No') }}</label>
                            <input type="text" name="hold_no" id="hold_no_{{ $saleHold->id }}"
                                class="form-control @error('hold_no') is-invalid @enderror"
                                value="{{ old('hold_no', $saleHold->hold_no) }}" required>
                            @error('hold_no')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="customer_id_{{ $saleHold->id }}">{{ __('Customer') }}</label>
                            <select name="customer_id" id="customer_id_{{ $saleHold->id }}"
                                class="form-select singl-select-2 @error('customer_id') is-invalid @enderror">
                                <option value="">{{ __('Walk-in') }}</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}"
                                        @selected(old('customer_id', $saleHold->customer_id) === $customer->id)>
                                        {{ $customer->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('customer_id')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label"
                                for="expires_at_{{ $saleHold->id }}">{{ __('Expires At') }}</label>
                            <input type="datetime-local" name="expires_at" id="expires_at_{{ $saleHold->id }}"
                                class="form-control @error('expires_at') is-invalid @enderror"
                                value="{{ old('expires_at', $saleHold->expires_at?->format('Y-m-d\TH:i')) }}">
                            @error('expires_at')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label" for="payload_{{ $saleHold->id }}">{{ __('Payload (JSON)') }}</label>
                            <textarea name="payload" id="payload_{{ $saleHold->id }}" rows="6"
                                class="form-control @error('payload') is-invalid @enderror" required>{{ old('payload', json_encode($saleHold->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) }}</textarea>
                            @error('payload')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary"
                        data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

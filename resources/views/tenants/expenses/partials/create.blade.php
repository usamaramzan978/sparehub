    <div class="modal fade" id="expenseCreateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Add Expense') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('tenant.expenses.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="expense-title">{{ __('Title') }}</label>
                                <input type="text" name="title" id="expense-title"
                                    class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}"
                                    required>
                                @error('title')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="expense-category">{{ __('Category') }}</label>
                                <input type="text" name="category" id="expense-category"
                                    class="form-control @error('category') is-invalid @enderror" value="{{ old('category') }}"
                                    placeholder="{{ __('Lunch / Fuel / Misc') }}">
                                @error('category')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="expense-amount">{{ __('Amount') }}</label>
                                <input type="number" step="0.01" min="0.01" name="amount" id="expense-amount"
                                    class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}"
                                    required>
                                @error('amount')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="expense-payment-method">{{ __('Payment Method') }}</label>
                                <select name="payment_method" id="expense-payment-method"
                                    class="form-select singl-select-2 @error('payment_method') is-invalid @enderror" required>
                                    @foreach ($methods as $method)
                                        <option value="{{ $method->value }}" @selected(old('payment_method', 'cash') === $method->value)>
                                            {{ ucfirst($method->value) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('payment_method')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="expense-date">{{ __('Expense Date') }}</label>
                                <input type="date" name="expense_date" id="expense-date"
                                    class="form-control @error('expense_date') is-invalid @enderror"
                                    value="{{ old('expense_date', now()->toDateString()) }}" required>
                                @error('expense_date')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="expense-reference-no">{{ __('Reference No') }}</label>
                                <input type="text" name="reference_no" id="expense-reference-no"
                                    class="form-control @error('reference_no') is-invalid @enderror"
                                    value="{{ old('reference_no') }}">
                                @error('reference_no')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-12">
                                <label class="form-label" for="expense-notes">{{ __('Notes') }}</label>
                                <textarea name="notes" id="expense-notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Create') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

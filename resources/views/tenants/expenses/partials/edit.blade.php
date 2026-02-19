    <div class="modal fade" id="expenseEditModal-{{ $expense->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Edit Expense') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('tenant.expenses.update', $expense) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="expense-title-{{ $expense->id }}">{{ __('Title') }}</label>
                                <input type="text" name="title" id="expense-title-{{ $expense->id }}"
                                    class="form-control @error('title') is-invalid @enderror"
                                    value="{{ old('title', $expense->title) }}" required>
                                @error('title')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="expense-category-{{ $expense->id }}">{{ __('Category') }}</label>
                                <input type="text" name="category" id="expense-category-{{ $expense->id }}"
                                    class="form-control @error('category') is-invalid @enderror"
                                    value="{{ old('category', $expense->category) }}">
                                @error('category')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="expense-amount-{{ $expense->id }}">{{ __('Amount') }}</label>
                                <input type="number" step="0.01" min="0.01" name="amount"
                                    id="expense-amount-{{ $expense->id }}"
                                    class="form-control @error('amount') is-invalid @enderror"
                                    value="{{ old('amount', (float) $expense->amount) }}" required>
                                @error('amount')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label"
                                    for="expense-payment-method-{{ $expense->id }}">{{ __('Payment Method') }}</label>
                                <select name="payment_method" id="expense-payment-method-{{ $expense->id }}"
                                    class="form-select singl-select-2 @error('payment_method') is-invalid @enderror" required>
                                    @foreach ($methods as $method)
                                        <option value="{{ $method->value }}"
                                            @selected(old('payment_method', $expense->payment_method->value) === $method->value)>
                                            {{ ucfirst($method->value) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('payment_method')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="expense-date-{{ $expense->id }}">{{ __('Expense Date') }}</label>
                                <input type="date" name="expense_date" id="expense-date-{{ $expense->id }}"
                                    class="form-control @error('expense_date') is-invalid @enderror"
                                    value="{{ old('expense_date', $expense->expense_date?->format('Y-m-d')) }}" required>
                                @error('expense_date')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"
                                    for="expense-reference-no-{{ $expense->id }}">{{ __('Reference No') }}</label>
                                <input type="text" name="reference_no" id="expense-reference-no-{{ $expense->id }}"
                                    class="form-control @error('reference_no') is-invalid @enderror"
                                    value="{{ old('reference_no', $expense->reference_no) }}">
                                @error('reference_no')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-12">
                                <label class="form-label" for="expense-notes-{{ $expense->id }}">{{ __('Notes') }}</label>
                                <textarea name="notes" id="expense-notes-{{ $expense->id }}"
                                    class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes', $expense->notes) }}</textarea>
                                @error('notes')
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

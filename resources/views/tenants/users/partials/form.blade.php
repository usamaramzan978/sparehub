@php
    $formMethod = strtoupper($formMethod ?? 'POST');
    $currentUser = $user ?? null;

    $commissionRules = old('commission_rules');

    if (! is_array($commissionRules)) {
        if ($currentUser) {
            $commissionRules = $currentUser->commissionRules->map(fn ($rule): array => [
                'service_catalog_id' => $rule->service_catalog_id,
                'total_amount' => (string) $rule->total_amount,
                'commission_type' => $rule->commission_type->value,
                'commission_value' => (string) $rule->commission_value,
            ])->values()->all();
        } else {
            $commissionRules = [];
        }
    }

    $labourServices = $labourServices ?? collect();
@endphp

<form method="POST" action="{{ $formAction }}" enctype="multipart/form-data">
    @csrf
    @if ($formMethod !== 'POST')
        @method($formMethod)
    @endif

    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label" for="name">{{ __('Name') }}</label>
            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                value="{{ old('name', $currentUser?->name) }}" required>
            @error('name')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-4 mb-3">
            <label class="form-label" for="email">{{ __('Email') }}</label>
            <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
                value="{{ old('email', $currentUser?->email) }}" required>
            @error('email')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-4 mb-3">
            <label class="form-label" for="phone">{{ __('Phone') }}</label>
            <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror"
                value="{{ old('phone', $currentUser?->phone) }}">
            @error('phone')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-4 mb-3">
            <label class="form-label" for="cnic">{{ __('CNIC') }}</label>
            <input type="text" name="cnic" id="cnic" class="form-control @error('cnic') is-invalid @enderror"
                value="{{ old('cnic', $currentUser?->cnic) }}">
            @error('cnic')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-4 mb-3">
            <label class="form-label" for="password">{{ $currentUser ? __('New Password') : __('Password') }}</label>
            <input type="password" name="password" id="password"
                class="form-control @error('password') is-invalid @enderror" @required(!$currentUser)>
            @if ($currentUser)
                <div class="form-text">{{ __('Leave blank to keep current password.') }}</div>
            @endif
            @error('password')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-4 mb-3">
            <label class="form-label" for="password_confirmation">
                {{ $currentUser ? __('Confirm New Password') : __('Confirm Password') }}
            </label>
            <input type="password" name="password_confirmation" id="password_confirmation"
                class="form-control @error('password_confirmation') is-invalid @enderror" @required(!$currentUser)>
            @error('password_confirmation')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-4 mb-3">
            <label class="form-label" for="status">{{ __('Status') }}</label>
            <select name="status" id="status" class="form-select singl-select-2 @error('status') is-invalid @enderror"
                required>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}"
                        @selected(old('status', $currentUser?->status?->value ?? 'active') === $status->value)>
                        {{ ucfirst($status->value) }}
                    </option>
                @endforeach
            </select>
            @error('status')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-4 mb-3">
            <label class="form-label" for="image">{{ __('Image') }}</label>
            <input type="file" name="image" id="image" accept="image/*"
                class="form-control @error('image') is-invalid @enderror">
            @if ($currentUser?->image_path)
                <div class="mt-2">
                    <a href="{{ asset('storage/' . $currentUser->image_path) }}" target="_blank" rel="noopener">
                        <img src="{{ asset('storage/' . $currentUser->image_path) }}" alt="{{ $currentUser->name }}"
                            style="width: 56px; height: 56px; object-fit: cover; border-radius: 6px;">
                    </a>
                </div>
            @endif
            @error('image')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-12 mb-3">
            <div class="card border">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="mb-0">{{ __('Commission Rules') }}</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="add-commission-rule"
                        @disabled($labourServices->isEmpty())>
                        {{ __('+ Add Rule') }}
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="min-width: 240px;">{{ __('Labour Service') }}</th>
                                    <th style="min-width: 140px;">{{ __('Total Amount') }}</th>
                                    <th style="min-width: 150px;">{{ __('Commission Type') }}</th>
                                    <th style="min-width: 150px;">{{ __('Commission Value') }}</th>
                                    <th style="min-width: 150px;">{{ __('Payable') }}</th>
                                    <th style="width: 70px;"></th>
                                </tr>
                            </thead>
                            <tbody id="commission-rules-body" data-next-index="{{ count($commissionRules) }}">
                                @foreach ($commissionRules as $index => $rule)
                                    <tr class="commission-rule-row" data-index="{{ $index }}">
                                        <td>
                                            <select name="commission_rules[{{ $index }}][service_catalog_id]"
                                                class="form-select singl-select-2" required>
                                                <option value="">{{ __('Select service') }}</option>
                                                @foreach ($labourServices as $labourService)
                                                    <option value="{{ $labourService->id }}"
                                                        @selected(($rule['service_catalog_id'] ?? '') === $labourService->id)>
                                                        {{ $labourService->code }} - {{ $labourService->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error("commission_rules.$index.service_catalog_id")
                                                <span class="text-danger small d-block">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td>
                                            <input type="number" step="1" min="0"
                                                name="commission_rules[{{ $index }}][total_amount]"
                                                class="form-control commission-total-amount"
                                                value="{{ $rule['total_amount'] ?? '0' }}" required>
                                            @error("commission_rules.$index.total_amount")
                                                <span class="text-danger small d-block">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td>
                                            <select name="commission_rules[{{ $index }}][commission_type]"
                                                class="form-select commission-type" required>
                                                <option value="fixed" @selected(($rule['commission_type'] ?? 'fixed') === 'fixed')>{{ __('Fixed') }}</option>
                                                <option value="percentage" @selected(($rule['commission_type'] ?? 'fixed') === 'percentage')>{{ __('Percentage') }}</option>
                                            </select>
                                            @error("commission_rules.$index.commission_type")
                                                <span class="text-danger small d-block">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td>
                                            <input type="number" step="1" min="0"
                                                name="commission_rules[{{ $index }}][commission_value]"
                                                class="form-control commission-value"
                                                value="{{ $rule['commission_value'] ?? '0' }}" required>
                                            @error("commission_rules.$index.commission_value")
                                                <span class="text-danger small d-block">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td>
                                            <input type="text" class="form-control commission-payable" value="0.00"
                                                readonly>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-danger-light remove-commission-rule">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @error('commission_rules')
                <span class="text-danger small d-block">{{ $message }}</span>
            @enderror
            @if ($labourServices->isEmpty())
                <span class="text-muted small d-block mt-2">
                    {{ __('No labour services found. Create labour type entries in Service Catalog first.') }}
                </span>
            @endif
        </div>
    </div>

    <div class="text-muted small mb-3">
        {{ __('Current Branch') }}: <strong>{{ $branch?->name ?? '-' }}</strong>
    </div>

    <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
</form>

<template id="commission-rule-template">
    <tr class="commission-rule-row" data-index="__INDEX__">
        <td>
            <select name="commission_rules[__INDEX__][service_catalog_id]" class="form-select singl-select-2" required>
                <option value="">{{ __('Select service') }}</option>
                @foreach ($labourServices as $labourService)
                    <option value="{{ $labourService->id }}">{{ $labourService->code }} - {{ $labourService->name }}
                    </option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="number" step="1" min="0" name="commission_rules[__INDEX__][total_amount]"
                class="form-control commission-total-amount" value="0" required>
        </td>
        <td>
            <select name="commission_rules[__INDEX__][commission_type]" class="form-select commission-type" required>
                <option value="fixed">{{ __('Fixed') }}</option>
                <option value="percentage">{{ __('Percentage') }}</option>
            </select>
        </td>
        <td>
            <input type="number" step="1" min="0" name="commission_rules[__INDEX__][commission_value]"
                class="form-control commission-value" value="0" required>
        </td>
        <td><input type="text" class="form-control commission-payable" value="0.00" readonly></td>
        <td>
            <button type="button" class="btn btn-sm btn-danger-light remove-commission-rule">
                <i class="ri-delete-bin-line"></i>
            </button>
        </td>
    </tr>
</template>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const rulesBody = document.getElementById('commission-rules-body');
            if (!rulesBody) {
                return;
            }

            const parseNumber = (value) => {
                const parsed = parseFloat(value);
                return Number.isFinite(parsed) ? parsed : 0;
            };
            const initSelect2 = (element) => {
                if (!(window.jQuery && window.jQuery.fn && window.jQuery.fn.select2)) {
                    return;
                }

                const $element = window.jQuery(element);
                if ($element.data('select2')) {
                    return;
                }

                $element.select2({
                    width: '100%',
                });
            };

            const recalculateRow = (row) => {
                const totalAmount = parseNumber(row.querySelector('.commission-total-amount')?.value);
                const commissionType = row.querySelector('.commission-type')?.value ?? 'fixed';
                const commissionValue = parseNumber(row.querySelector('.commission-value')?.value);

                const payable = commissionType === 'percentage'
                    ? (totalAmount * commissionValue) / 100
                    : commissionValue;

                const payableInput = row.querySelector('.commission-payable');
                if (payableInput) {
                    payableInput.value = payable.toFixed(2);
                }
            };

            const addRule = () => {
                const template = document.getElementById('commission-rule-template');
                const index = parseInt(rulesBody.dataset.nextIndex ?? '0', 10);
                const html = template.innerHTML.replaceAll('__INDEX__', String(index));
                rulesBody.insertAdjacentHTML('beforeend', html);
                rulesBody.dataset.nextIndex = String(index + 1);

                const row = rulesBody.querySelector('.commission-rule-row:last-child');
                if (row) {
                    row.querySelectorAll('.singl-select-2').forEach((select) => initSelect2(select));
                    recalculateRow(row);
                }
            };

            document.getElementById('add-commission-rule')?.addEventListener('click', addRule);

            rulesBody.addEventListener('input', (event) => {
                const target = event.target;
                if (!(target instanceof HTMLElement)) {
                    return;
                }

                const row = target.closest('.commission-rule-row');
                if (!row) {
                    return;
                }

                recalculateRow(row);
            });

            rulesBody.addEventListener('change', (event) => {
                const target = event.target;
                if (!(target instanceof HTMLElement)) {
                    return;
                }

                const row = target.closest('.commission-rule-row');
                if (!row) {
                    return;
                }

                recalculateRow(row);
            });

            rulesBody.addEventListener('click', (event) => {
                const target = event.target;
                if (!(target instanceof HTMLElement)) {
                    return;
                }

                const removeButton = target.closest('.remove-commission-rule');
                if (!removeButton) {
                    return;
                }

                removeButton.closest('.commission-rule-row')?.remove();
            });

            document.querySelectorAll('.singl-select-2').forEach((select) => initSelect2(select));
            rulesBody.querySelectorAll('.commission-rule-row').forEach((row) => recalculateRow(row));
        });
    </script>
@endpush

@php
    $formMethod = strtoupper($formMethod ?? 'POST');
    $currentPurchase = $purchase ?? null;
    $isEditForm = $formMethod !== 'POST';

    $lineItems = old('items');

    if (!is_array($lineItems)) {
        if ($currentPurchase) {
            $lineItems = $currentPurchase->items
                ->map(
                    fn($item): array => [
                        'product_id' => $item->product_id,
                        'qty' => (string) $item->qty,
                        'cost' => (string) $item->unit_cost,
                        'mrp' => (string) ($item->mrp ?? '0'),
                        'retail_price' => (string) ($item->retail_price ?? '0'),
                        'wholesale_price' => (string) ($item->wholesale_price ?? '0'),
                    ],
                )
                ->values()
                ->all();
        } else {
            $lineItems = [
                [
                    'product_id' => '',
                    'qty' => '1',
                    'cost' => '0',
                    'mrp' => '0',
                    'retail_price' => '0',
                    'wholesale_price' => '0',
                ],
            ];
        }
    }
@endphp

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $formAction }}">
    @csrf
    @if ($formMethod !== 'POST')
        @method($formMethod)
    @endif

    <div class="row">
        <div class="col-md-3 mb-3">
            <label class="form-label" for="purchase_no">{{ __('Purchase No') }}</label>
            <input type="text" name="purchase_no" id="purchase_no"
                class="form-control @error('purchase_no') is-invalid @enderror"
                value="{{ old('purchase_no', $currentPurchase?->purchase_no) }}" @readonly($isEditForm)>
            <small class="text-muted">
                {{ $isEditForm ? __('Purchase no is locked after creation.') : __('Leave empty to let the system generate purchase no.') }}
            </small>
            @error('purchase_no')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-3 mb-3">
            <label class="form-label" for="purchase_date">{{ __('Purchase Date') }}</label>
            <input type="date" name="purchase_date" id="purchase_date"
                class="form-control @error('purchase_date') is-invalid @enderror"
                value="{{ old('purchase_date', \App\Support\TenantDateTime::format($currentPurchase?->purchase_date ?? now(), 'Y-m-d', '')) }}"
                required>
            @error('purchase_date')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-3 mb-3">
            <label class="form-label" for="due_date">{{ __('Due Date') }}</label>
            <input type="date" name="due_date" id="due_date"
                class="form-control @error('due_date') is-invalid @enderror"
                value="{{ old('due_date', \App\Support\TenantDateTime::format($currentPurchase?->due_date, 'Y-m-d', '')) }}">
            @error('due_date')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-3 mb-3">
            <label class="form-label" for="vendor_id">{{ __('Vendor') }}</label>
            <select name="vendor_id" id="vendor_id"
                class="form-select singl-select-2 @error('vendor_id') is-invalid @enderror" required>
                <option value="">{{ __('Select vendor') }}</option>
                @foreach ($vendors as $vendor)
                    <option value="{{ $vendor->id }}" @selected(old('vendor_id', $currentPurchase?->vendor_id) === $vendor->id)>
                        {{ $vendor->name }}
                    </option>
                @endforeach
            </select>
            @error('vendor_id')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-4 mb-3">
            <label class="form-label" for="status">{{ __('Status') }}</label>
            <select name="status" id="status"
                class="form-select singl-select-2 @error('status') is-invalid @enderror" required>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(old('status', $currentPurchase?->status?->value ?? 'draft') === $status->value)>
                        {{ ucfirst(str_replace('_', ' ', $status->value)) }}
                    </option>
                @endforeach
            </select>
            @error('status')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-12 mb-3">
            <div class="card border">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="mb-0">{{ __('Purchase Items') }}</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary"
                        id="add-purchase-item">{{ __('+ Add Product') }}</button>
                </div>
                <div class="card-body border-bottom py-2">
                    <small
                        class="text-muted">{{ __('Note: qty updates stock, and cost/MRP/retail/wholesale values are automatically synced to product pricing.') }}</small>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="min-width:220px;">{{ __('Product') }}</th>
                                    <th style="min-width:90px;">{{ __('Qty') }}</th>
                                    <th style="min-width:120px;">{{ __('Cost') }}</th>
                                    <th style="min-width:120px;">{{ __('MRP') }}</th>
                                    <th style="min-width:120px;">{{ __('Retail Price') }}</th>
                                    <th style="min-width:140px;">{{ __('Wholesale Price') }}</th>
                                    <th style="width:70px;"></th>
                                </tr>
                            </thead>
                            <tbody id="purchase-items-body" data-next-index="{{ count($lineItems) }}">
                                @foreach ($lineItems as $index => $item)
                                    <tr class="purchase-item-row" data-index="{{ $index }}">
                                        <td>
                                            <select name="items[{{ $index }}][product_id]"
                                                class="form-select singl-select-2" required>
                                                <option value="">{{ __('Select product') }}</option>
                                                @foreach ($products as $product)
                                                    <option value="{{ $product->id }}" @selected(($item['product_id'] ?? '') === $product->id)>
                                                        {{ $product->name }}
                                                        {{ $product->sku ? '(' . $product->sku . ')' : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error("items.$index.product_id")
                                                <span class="text-danger small d-block">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td><input type="number" step="0.001" min="0.001"
                                                name="items[{{ $index }}][qty]"
                                                class="form-control purchase-item-qty"
                                                value="{{ $item['qty'] ?? '1' }}" required></td>
                                        <td><input type="number" step="0.01" min="0"
                                                name="items[{{ $index }}][cost]"
                                                class="form-control purchase-item-cost"
                                                value="{{ $item['cost'] ?? '0' }}" required></td>
                                        <td><input type="number" step="0.01" min="0"
                                                name="items[{{ $index }}][mrp]" class="form-control"
                                                value="{{ $item['mrp'] ?? '0' }}" required></td>
                                        <td><input type="number" step="0.01" min="0"
                                                name="items[{{ $index }}][retail_price]" class="form-control"
                                                value="{{ $item['retail_price'] ?? '0' }}" required></td>
                                        <td><input type="number" step="0.01" min="0"
                                                name="items[{{ $index }}][wholesale_price]"
                                                class="form-control" value="{{ $item['wholesale_price'] ?? '0' }}"
                                                required></td>
                                        <td>
                                            <button type="button"
                                                class="btn btn-sm btn-danger-light purchase-remove-item"><i
                                                    class="ri-delete-bin-line"></i></button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @error('items')
                <span class="text-danger small d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-3 mb-3">
            <label class="form-label" for="grand_total_preview">{{ __('Grand Total') }}</label>
            <input type="text" id="grand_total_preview" class="form-control"
                value="{{ number_format((float) ($currentPurchase?->grand_total ?? 0), 2) }}" readonly>
        </div>

        <div class="col-md-12 mb-3">
            <label class="form-label" for="notes">{{ __('Notes') }}</label>
            <textarea name="notes" id="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $currentPurchase?->notes) }}</textarea>
            @error('notes')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <button class="btn btn-primary" type="submit">{{ $submitLabel }}</button>
</form>

<template id="purchase-item-row-template">
    <tr class="purchase-item-row" data-index="__INDEX__">
        <td>
            <select name="items[__INDEX__][product_id]" class="form-select singl-select-2" required>
                <option value="">{{ __('Select product') }}</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }}
                        {{ $product->sku ? '(' . $product->sku . ')' : '' }}</option>
                @endforeach
            </select>
        </td>
        <td><input type="number" step="0.001" min="0.001" name="items[__INDEX__][qty]"
                class="form-control purchase-item-qty" value="1" required></td>
        <td><input type="number" step="0.01" min="0" name="items[__INDEX__][cost]"
                class="form-control purchase-item-cost" value="0" required></td>
        <td><input type="number" step="0.01" min="0" name="items[__INDEX__][mrp]" class="form-control"
                value="0" required></td>
        <td><input type="number" step="0.01" min="0" name="items[__INDEX__][retail_price]"
                class="form-control" value="0" required></td>
        <td><input type="number" step="0.01" min="0" name="items[__INDEX__][wholesale_price]"
                class="form-control" value="0" required></td>
        <td><button type="button" class="btn btn-sm btn-danger-light purchase-remove-item"><i
                    class="ri-delete-bin-line"></i></button></td>
    </tr>
</template>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const body = document.getElementById('purchase-items-body');
            if (!body) {
                return;
            }

            const rowTemplate = document.getElementById('purchase-item-row-template');
            const addButton = document.getElementById('add-purchase-item');
            const grandTotalInput = document.getElementById('grand_total_preview');

            const parseNumber = (value) => {
                const parsed = parseFloat(value);
                return Number.isFinite(parsed) ? parsed : 0;
            };

            const initSelect2 = (element) => {
                if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) {
                    return;
                }

                const $el = window.jQuery(element);
                if ($el.hasClass('select2-hidden-accessible')) {
                    return;
                }

                $el.select2({
                    width: '100%'
                });
            };

            const recalculateRow = (row) => {
                const qty = parseNumber(row.querySelector('.purchase-item-qty')?.value);
                const cost = parseNumber(row.querySelector('.purchase-item-cost')?.value);
                const lineTotal = qty * cost;

                return {
                    sub: lineTotal,
                    total: lineTotal,
                };
            };

            const recalculateTotals = () => {
                const rows = body.querySelectorAll('.purchase-item-row');
                let total = 0;

                rows.forEach((row) => {
                    const rowTotals = recalculateRow(row);
                    total += rowTotals.total;
                });
                if (grandTotalInput) grandTotalInput.value = total.toFixed(2);
            };

            const initializeRow = (row) => {
                row.querySelectorAll('.singl-select-2').forEach((select) => initSelect2(select));
                recalculateRow(row);
            };

            const addRow = () => {
                let nextIndex = parseInt(body.dataset.nextIndex ?? '0', 10);
                if (!Number.isFinite(nextIndex)) {
                    nextIndex = body.querySelectorAll('.purchase-item-row').length;
                }

                let template = rowTemplate.innerHTML;
                template = template.replaceAll('__INDEX__', String(nextIndex));

                body.insertAdjacentHTML('beforeend', template);
                body.dataset.nextIndex = String(nextIndex + 1);

                const row = body.querySelector('.purchase-item-row:last-child');
                if (row) {
                    initializeRow(row);
                }

                recalculateTotals();
            };

            addButton?.addEventListener('click', addRow);

            body.addEventListener('input', (event) => {
                const target = event.target;
                if (!(target instanceof HTMLElement) || !target.closest('.purchase-item-row')) {
                    return;
                }

                recalculateTotals();
            });

            body.addEventListener('click', (event) => {
                const button = event.target instanceof HTMLElement ? event.target.closest(
                    '.purchase-remove-item') : null;
                if (!button) {
                    return;
                }

                const row = button.closest('.purchase-item-row');
                row?.remove();

                if (!body.querySelector('.purchase-item-row')) {
                    addRow();
                }

                recalculateTotals();
            });

            body.querySelectorAll('.purchase-item-row').forEach((row) => initializeRow(row));
            recalculateTotals();
        });
    </script>
@endpush

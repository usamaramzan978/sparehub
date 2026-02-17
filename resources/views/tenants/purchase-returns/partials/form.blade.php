@php
    $formMethod = strtoupper($formMethod ?? 'POST');
    $currentPurchaseReturn = $purchaseReturn ?? null;

    $lineItems = old('items');

    if (! is_array($lineItems)) {
        if ($currentPurchaseReturn) {
            $lineItems = $currentPurchaseReturn->items->map(fn ($item): array => [
                'purchase_item_id' => $item->purchase_item_id,
                'product_id' => $item->product_id,
                'tax_id' => $item->tax_id,
                'qty' => (string) $item->qty,
                'unit_cost' => (string) $item->unit_cost,
                'tax_amount' => (string) $item->tax_amount,
                'remarks' => $item->remarks,
            ])->values()->all();
        } else {
            $lineItems = [[
                'purchase_item_id' => '',
                'product_id' => '',
                'tax_id' => '',
                'qty' => '1',
                'unit_cost' => '0',
                'tax_amount' => '0',
                'remarks' => '',
            ]];
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
            <label class="form-label" for="return_no">{{ __('Return No') }}</label>
            <input type="text" name="return_no" id="return_no"
                class="form-control @error('return_no') is-invalid @enderror"
                value="{{ old('return_no', $currentPurchaseReturn?->return_no) }}" required>
            @error('return_no')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-3 mb-3">
            <label class="form-label" for="return_date">{{ __('Return Date') }}</label>
            <input type="date" name="return_date" id="return_date"
                class="form-control @error('return_date') is-invalid @enderror"
                value="{{ old('return_date', $currentPurchaseReturn?->return_date?->format('Y-m-d') ?? now()->toDateString()) }}" required>
            @error('return_date')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-3 mb-3">
            <label class="form-label" for="vendor_id">{{ __('Vendor') }}</label>
            <select name="vendor_id" id="vendor_id" class="form-select singl-select-2 @error('vendor_id') is-invalid @enderror" required>
                <option value="">{{ __('Select vendor') }}</option>
                @foreach ($vendors as $vendor)
                    <option value="{{ $vendor->id }}" @selected(old('vendor_id', $currentPurchaseReturn?->vendor_id) === $vendor->id)>
                        {{ $vendor->name }}
                    </option>
                @endforeach
            </select>
            @error('vendor_id')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-3 mb-3">
            <label class="form-label" for="purchase_id">{{ __('Purchase') }}</label>
            <select name="purchase_id" id="purchase_id" class="form-select singl-select-2 @error('purchase_id') is-invalid @enderror">
                <option value="">{{ __('None') }}</option>
                @foreach ($purchases as $purchase)
                    <option value="{{ $purchase->id }}" @selected(old('purchase_id', $currentPurchaseReturn?->purchase_id) === $purchase->id)>
                        {{ $purchase->purchase_no }}
                    </option>
                @endforeach
            </select>
            @error('purchase_id')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-4 mb-3">
            <label class="form-label" for="status">{{ __('Status') }}</label>
            <select name="status" id="status" class="form-select singl-select-2 @error('status') is-invalid @enderror" required>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(old('status', $currentPurchaseReturn?->status?->value ?? 'draft') === $status->value)>
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
                    <h6 class="mb-0">{{ __('Return Items') }}</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="add-purchase-return-item">{{ __('+ Add Product') }}</button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="min-width:240px;">{{ __('Purchase Item') }}</th>
                                    <th style="min-width:220px;">{{ __('Product') }}</th>
                                    <th style="min-width:150px;">{{ __('Tax') }}</th>
                                    <th style="min-width:90px;">{{ __('Qty') }}</th>
                                    <th style="min-width:120px;">{{ __('Unit Cost') }}</th>
                                    <th style="min-width:120px;">{{ __('Tax Amount') }}</th>
                                    <th style="min-width:160px;">{{ __('Remarks') }}</th>
                                    <th style="min-width:120px;">{{ __('Line Total') }}</th>
                                    <th style="width:70px;"></th>
                                </tr>
                            </thead>
                            <tbody id="purchase-return-items-body" data-next-index="{{ count($lineItems) }}">
                                @foreach ($lineItems as $index => $item)
                                    <tr class="purchase-return-item-row" data-index="{{ $index }}">
                                        <td>
                                            <select name="items[{{ $index }}][purchase_item_id]" class="form-select singl-select-2 purchase-return-item-source">
                                                <option value="">{{ __('None') }}</option>
                                                @foreach ($purchaseItems as $purchaseItem)
                                                    <option
                                                        value="{{ $purchaseItem->id }}"
                                                        data-product-id="{{ $purchaseItem->product_id }}"
                                                        data-unit-cost="{{ (float) $purchaseItem->unit_cost }}"
                                                        @selected(($item['purchase_item_id'] ?? '') === $purchaseItem->id)
                                                    >
                                                        {{ $purchaseItem->purchase?->purchase_no ?? '-' }} - {{ $purchaseItem->product?->name ?? '-' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select name="items[{{ $index }}][product_id]" class="form-select singl-select-2 purchase-return-item-product" required>
                                                <option value="">{{ __('Select product') }}</option>
                                                @foreach ($products as $product)
                                                    <option value="{{ $product->id }}" @selected(($item['product_id'] ?? '') === $product->id)>
                                                        {{ $product->name }} {{ $product->sku ? '(' . $product->sku . ')' : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error("items.$index.product_id")
                                                <span class="text-danger small d-block">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td>
                                            <select name="items[{{ $index }}][tax_id]" class="form-select singl-select-2">
                                                <option value="">{{ __('None') }}</option>
                                                @foreach ($taxes as $tax)
                                                    <option value="{{ $tax->id }}" @selected(($item['tax_id'] ?? '') === $tax->id)>
                                                        {{ $tax->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="number" step="0.001" min="0.001" name="items[{{ $index }}][qty]" class="form-control purchase-return-item-qty" value="{{ $item['qty'] ?? '1' }}" required></td>
                                        <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][unit_cost]" class="form-control purchase-return-item-cost" value="{{ $item['unit_cost'] ?? '0' }}" required></td>
                                        <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][tax_amount]" class="form-control purchase-return-item-tax" value="{{ $item['tax_amount'] ?? '0' }}"></td>
                                        <td><input type="text" name="items[{{ $index }}][remarks]" class="form-control" value="{{ $item['remarks'] ?? '' }}"></td>
                                        <td><input type="text" class="form-control purchase-return-item-total" value="0.00" readonly></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-danger-light purchase-return-remove-item"><i class="ri-delete-bin-line"></i></button>
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
            <label class="form-label" for="sub_total">{{ __('Sub Total') }}</label>
            <input type="number" step="0.01" name="sub_total" id="sub_total"
                class="form-control @error('sub_total') is-invalid @enderror" value="{{ old('sub_total', (string) ($currentPurchaseReturn?->sub_total ?? '0')) }}" readonly>
            @error('sub_total')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-3 mb-3">
            <label class="form-label" for="tax_total">{{ __('Tax') }}</label>
            <input type="number" step="0.01" name="tax_total" id="tax_total"
                class="form-control @error('tax_total') is-invalid @enderror" value="{{ old('tax_total', (string) ($currentPurchaseReturn?->tax_total ?? '0')) }}" readonly>
            @error('tax_total')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-3 mb-3">
            <label class="form-label" for="grand_total">{{ __('Grand Total') }}</label>
            <input type="number" step="0.01" name="grand_total" id="grand_total"
                class="form-control @error('grand_total') is-invalid @enderror" value="{{ old('grand_total', (string) ($currentPurchaseReturn?->grand_total ?? '0')) }}" readonly>
            @error('grand_total')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-3 mb-3">
            <label class="form-label" for="posted_at">{{ __('Posted At') }}</label>
            <input type="datetime-local" name="posted_at" id="posted_at"
                class="form-control @error('posted_at') is-invalid @enderror"
                value="{{ old('posted_at', $currentPurchaseReturn?->posted_at?->format('Y-m-d\\TH:i')) }}">
            @error('posted_at')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-12 mb-3">
            <label class="form-label" for="notes">{{ __('Notes') }}</label>
            <textarea name="notes" id="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $currentPurchaseReturn?->notes) }}</textarea>
            @error('notes')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
</form>

<template id="purchase-return-item-row-template">
    <tr class="purchase-return-item-row" data-index="__INDEX__">
        <td>
            <select name="items[__INDEX__][purchase_item_id]" class="form-select singl-select-2 purchase-return-item-source">
                <option value="">{{ __('None') }}</option>
                @foreach ($purchaseItems as $purchaseItem)
                    <option value="{{ $purchaseItem->id }}" data-product-id="{{ $purchaseItem->product_id }}" data-unit-cost="{{ (float) $purchaseItem->unit_cost }}">
                        {{ $purchaseItem->purchase?->purchase_no ?? '-' }} - {{ $purchaseItem->product?->name ?? '-' }}
                    </option>
                @endforeach
            </select>
        </td>
        <td>
            <select name="items[__INDEX__][product_id]" class="form-select singl-select-2 purchase-return-item-product" required>
                <option value="">{{ __('Select product') }}</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }} {{ $product->sku ? '(' . $product->sku . ')' : '' }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <select name="items[__INDEX__][tax_id]" class="form-select singl-select-2">
                <option value="">{{ __('None') }}</option>
                @foreach ($taxes as $tax)
                    <option value="{{ $tax->id }}">{{ $tax->name }}</option>
                @endforeach
            </select>
        </td>
        <td><input type="number" step="0.001" min="0.001" name="items[__INDEX__][qty]" class="form-control purchase-return-item-qty" value="1" required></td>
        <td><input type="number" step="0.01" min="0" name="items[__INDEX__][unit_cost]" class="form-control purchase-return-item-cost" value="0" required></td>
        <td><input type="number" step="0.01" min="0" name="items[__INDEX__][tax_amount]" class="form-control purchase-return-item-tax" value="0"></td>
        <td><input type="text" name="items[__INDEX__][remarks]" class="form-control"></td>
        <td><input type="text" class="form-control purchase-return-item-total" value="0.00" readonly></td>
        <td><button type="button" class="btn btn-sm btn-danger-light purchase-return-remove-item"><i class="ri-delete-bin-line"></i></button></td>
    </tr>
</template>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const body = document.getElementById('purchase-return-items-body');
            if (!body) {
                return;
            }

            const rowTemplate = document.getElementById('purchase-return-item-row-template');
            const addButton = document.getElementById('add-purchase-return-item');
            const subTotalInput = document.getElementById('sub_total');
            const taxTotalInput = document.getElementById('tax_total');
            const grandTotalInput = document.getElementById('grand_total');

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

                $el.select2({ width: '100%' });
            };

            const syncFromPurchaseItem = (row) => {
                const purchaseItemSelect = row.querySelector('.purchase-return-item-source');
                if (!(purchaseItemSelect instanceof HTMLSelectElement)) {
                    return;
                }

                const selectedOption = purchaseItemSelect.options[purchaseItemSelect.selectedIndex];
                if (!selectedOption) {
                    return;
                }

                const productId = selectedOption.dataset.productId ?? '';
                const unitCost = selectedOption.dataset.unitCost ?? '';

                const productSelect = row.querySelector('.purchase-return-item-product');
                const unitCostInput = row.querySelector('.purchase-return-item-cost');

                if (productSelect instanceof HTMLSelectElement && productId !== '') {
                    productSelect.value = productId;
                    if (window.jQuery) {
                        window.jQuery(productSelect).trigger('change.select2');
                    }
                }

                if (unitCostInput instanceof HTMLInputElement && unitCost !== '') {
                    unitCostInput.value = Number(parseNumber(unitCost)).toFixed(2);
                }
            };

            const recalculateRow = (row) => {
                const qty = parseNumber(row.querySelector('.purchase-return-item-qty')?.value);
                const unitCost = parseNumber(row.querySelector('.purchase-return-item-cost')?.value);
                const tax = parseNumber(row.querySelector('.purchase-return-item-tax')?.value);
                const lineTotal = (qty * unitCost) + tax;

                const totalInput = row.querySelector('.purchase-return-item-total');
                if (totalInput) {
                    totalInput.value = lineTotal.toFixed(2);
                }

                return {
                    sub: qty * unitCost,
                    tax,
                    total: lineTotal,
                };
            };

            const recalculateTotals = () => {
                const rows = body.querySelectorAll('.purchase-return-item-row');
                let sub = 0;
                let tax = 0;
                let total = 0;

                rows.forEach((row) => {
                    const rowTotals = recalculateRow(row);
                    sub += rowTotals.sub;
                    tax += rowTotals.tax;
                    total += rowTotals.total;
                });

                if (subTotalInput) subTotalInput.value = sub.toFixed(2);
                if (taxTotalInput) taxTotalInput.value = tax.toFixed(2);
                if (grandTotalInput) grandTotalInput.value = total.toFixed(2);
            };

            const initializeRow = (row) => {
                row.querySelectorAll('.singl-select-2').forEach((select) => initSelect2(select));
                syncFromPurchaseItem(row);
                recalculateRow(row);
            };

            const addRow = () => {
                let nextIndex = parseInt(body.dataset.nextIndex ?? '0', 10);
                if (!Number.isFinite(nextIndex)) {
                    nextIndex = body.querySelectorAll('.purchase-return-item-row').length;
                }

                let template = rowTemplate.innerHTML;
                template = template.replaceAll('__INDEX__', String(nextIndex));

                body.insertAdjacentHTML('beforeend', template);
                body.dataset.nextIndex = String(nextIndex + 1);

                const row = body.querySelector('.purchase-return-item-row:last-child');
                if (row) {
                    initializeRow(row);
                }

                recalculateTotals();
            };

            addButton?.addEventListener('click', addRow);

            body.addEventListener('change', (event) => {
                const target = event.target;
                if (!(target instanceof HTMLElement)) {
                    return;
                }

                if (target.classList.contains('purchase-return-item-source')) {
                    const row = target.closest('.purchase-return-item-row');
                    if (row) {
                        syncFromPurchaseItem(row);
                        recalculateTotals();
                    }
                }
            });

            body.addEventListener('input', (event) => {
                const target = event.target;
                if (!(target instanceof HTMLElement) || !target.closest('.purchase-return-item-row')) {
                    return;
                }

                recalculateTotals();
            });

            body.addEventListener('click', (event) => {
                const button = event.target instanceof HTMLElement ? event.target.closest('.purchase-return-remove-item') : null;
                if (!button) {
                    return;
                }

                const row = button.closest('.purchase-return-item-row');
                row?.remove();

                if (!body.querySelector('.purchase-return-item-row')) {
                    addRow();
                }

                recalculateTotals();
            });

            body.querySelectorAll('.purchase-return-item-row').forEach((row) => initializeRow(row));
            recalculateTotals();
        });
    </script>
@endpush

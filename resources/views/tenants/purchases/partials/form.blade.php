@php
    $formMethod = strtoupper($formMethod ?? 'POST');
    $currentPurchase = $purchase ?? null;

    $lineItems = old('items');

    if (! is_array($lineItems)) {
        if ($currentPurchase) {
            $lineItems = $currentPurchase->items->map(fn ($item): array => [
                'product_id' => $item->product_id,
                'tax_id' => $item->tax_id,
                'qty' => (string) $item->qty,
                'unit_cost' => (string) $item->unit_cost,
                'discount_amount' => (string) $item->discount_amount,
                'tax_amount' => (string) $item->tax_amount,
                'remarks' => $item->remarks,
            ])->values()->all();
        } else {
            $lineItems = [[
                'product_id' => '',
                'tax_id' => '',
                'qty' => '1',
                'unit_cost' => '0',
                'discount_amount' => '0',
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
            <label class="form-label" for="purchase_no">{{ __('Purchase No') }}</label>
            <input type="text" name="purchase_no" id="purchase_no"
                class="form-control @error('purchase_no') is-invalid @enderror"
                value="{{ old('purchase_no', $currentPurchase?->purchase_no) }}" required>
            @error('purchase_no')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-3 mb-3">
            <label class="form-label" for="purchase_date">{{ __('Purchase Date') }}</label>
            <input type="date" name="purchase_date" id="purchase_date"
                class="form-control @error('purchase_date') is-invalid @enderror"
                value="{{ old('purchase_date', \App\Support\TenantDateTime::format($currentPurchase?->purchase_date ?? now(), 'Y-m-d', '')) }}" required>
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
            <label class="form-label" for="vendor_invoice_no">{{ __('Vendor Invoice No') }}</label>
            <input type="text" name="vendor_invoice_no" id="vendor_invoice_no"
                class="form-control @error('vendor_invoice_no') is-invalid @enderror"
                value="{{ old('vendor_invoice_no', $currentPurchase?->vendor_invoice_no) }}">
            @error('vendor_invoice_no')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-4 mb-3">
            <label class="form-label" for="vendor_id">{{ __('Vendor') }}</label>
            <select name="vendor_id" id="vendor_id" class="form-select singl-select-2 @error('vendor_id') is-invalid @enderror" required>
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
            <label class="form-label" for="warehouse_id">{{ __('Warehouse') }}</label>
            <select name="warehouse_id" id="warehouse_id" class="form-select singl-select-2 @error('warehouse_id') is-invalid @enderror">
                <option value="">{{ __('None') }}</option>
                @foreach ($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" @selected(old('warehouse_id', $currentPurchase?->warehouse_id) === $warehouse->id)>
                        {{ $warehouse->name }}
                    </option>
                @endforeach
            </select>
            @error('warehouse_id')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-4 mb-3">
            <label class="form-label" for="status">{{ __('Status') }}</label>
            <select name="status" id="status" class="form-select singl-select-2 @error('status') is-invalid @enderror" required>
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
                    <button type="button" class="btn btn-sm btn-outline-primary" id="add-purchase-item">{{ __('+ Add Product') }}</button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="min-width:220px;">{{ __('Product') }}</th>
                                    <th style="min-width:150px;">{{ __('Tax') }}</th>
                                    <th style="min-width:90px;">{{ __('Qty') }}</th>
                                    <th style="min-width:120px;">{{ __('Unit Cost') }}</th>
                                    <th style="min-width:120px;">{{ __('Discount') }}</th>
                                    <th style="min-width:120px;">{{ __('Tax Amount') }}</th>
                                    <th style="min-width:160px;">{{ __('Remarks') }}</th>
                                    <th style="min-width:120px;">{{ __('Line Total') }}</th>
                                    <th style="width:70px;"></th>
                                </tr>
                            </thead>
                            <tbody id="purchase-items-body" data-next-index="{{ count($lineItems) }}">
                                @foreach ($lineItems as $index => $item)
                                    <tr class="purchase-item-row" data-index="{{ $index }}">
                                        <td>
                                            <select name="items[{{ $index }}][product_id]" class="form-select singl-select-2" required>
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
                                        <td><input type="number" step="0.001" min="0.001" name="items[{{ $index }}][qty]" class="form-control purchase-item-qty" value="{{ $item['qty'] ?? '1' }}" required></td>
                                        <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][unit_cost]" class="form-control purchase-item-cost" value="{{ $item['unit_cost'] ?? '0' }}" required></td>
                                        <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][discount_amount]" class="form-control purchase-item-discount" value="{{ $item['discount_amount'] ?? '0' }}"></td>
                                        <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][tax_amount]" class="form-control purchase-item-tax" value="{{ $item['tax_amount'] ?? '0' }}"></td>
                                        <td><input type="text" name="items[{{ $index }}][remarks]" class="form-control" value="{{ $item['remarks'] ?? '' }}"></td>
                                        <td><input type="text" class="form-control purchase-item-total" value="0.00" readonly></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-danger-light purchase-remove-item"><i class="ri-delete-bin-line"></i></button>
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

        <div class="col-md-2 mb-3">
            <label class="form-label" for="sub_total">{{ __('Sub Total') }}</label>
            <input type="number" step="0.01" name="sub_total" id="sub_total" class="form-control @error('sub_total') is-invalid @enderror" value="{{ old('sub_total', (string) ($currentPurchase?->sub_total ?? '0')) }}" readonly>
            @error('sub_total')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
        </div>

        <div class="col-md-2 mb-3">
            <label class="form-label" for="discount_total">{{ __('Discount') }}</label>
            <input type="number" step="0.01" name="discount_total" id="discount_total" class="form-control @error('discount_total') is-invalid @enderror" value="{{ old('discount_total', (string) ($currentPurchase?->discount_total ?? '0')) }}" readonly>
            @error('discount_total')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
        </div>

        <div class="col-md-2 mb-3">
            <label class="form-label" for="tax_total">{{ __('Tax') }}</label>
            <input type="number" step="0.01" name="tax_total" id="tax_total" class="form-control @error('tax_total') is-invalid @enderror" value="{{ old('tax_total', (string) ($currentPurchase?->tax_total ?? '0')) }}" readonly>
            @error('tax_total')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
        </div>

        <div class="col-md-2 mb-3">
            <label class="form-label" for="shipping_total">{{ __('Shipping') }}</label>
            <input type="number" step="0.01" min="0" name="shipping_total" id="shipping_total" class="form-control @error('shipping_total') is-invalid @enderror" value="{{ old('shipping_total', (string) ($currentPurchase?->shipping_total ?? '0')) }}">
            @error('shipping_total')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
        </div>

        <div class="col-md-2 mb-3">
            <label class="form-label" for="grand_total">{{ __('Grand Total') }}</label>
            <input type="number" step="0.01" name="grand_total" id="grand_total" class="form-control @error('grand_total') is-invalid @enderror" value="{{ old('grand_total', (string) ($currentPurchase?->grand_total ?? '0')) }}" readonly>
            @error('grand_total')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
        </div>

        <div class="col-md-2 mb-3">
            <label class="form-label" for="paid_total">{{ __('Paid') }}</label>
            <input type="number" step="0.01" min="0" name="paid_total" id="paid_total" class="form-control @error('paid_total') is-invalid @enderror" value="{{ old('paid_total', (string) ($currentPurchase?->paid_total ?? '0')) }}">
            @error('paid_total')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
        </div>

        <div class="col-md-2 mb-3">
            <label class="form-label" for="balance_due">{{ __('Balance') }}</label>
            <input type="number" step="0.01" name="balance_due" id="balance_due" class="form-control @error('balance_due') is-invalid @enderror" value="{{ old('balance_due', (string) ($currentPurchase?->balance_due ?? '0')) }}" readonly>
            @error('balance_due')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
        </div>

        <div class="col-md-6 mb-3">
            <label class="form-label" for="posted_at">{{ __('Posted At') }}</label>
            <input type="datetime-local" name="posted_at" id="posted_at" class="form-control @error('posted_at') is-invalid @enderror" value="{{ old('posted_at', \App\Support\TenantDateTime::format($currentPurchase?->posted_at, 'Y-m-d\\TH:i', '')) }}">
            @error('posted_at')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
        </div>

        <div class="col-md-12 mb-3">
            <label class="form-label" for="notes">{{ __('Notes') }}</label>
            <textarea name="notes" id="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $currentPurchase?->notes) }}</textarea>
            @error('notes')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
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
        <td><input type="number" step="0.001" min="0.001" name="items[__INDEX__][qty]" class="form-control purchase-item-qty" value="1" required></td>
        <td><input type="number" step="0.01" min="0" name="items[__INDEX__][unit_cost]" class="form-control purchase-item-cost" value="0" required></td>
        <td><input type="number" step="0.01" min="0" name="items[__INDEX__][discount_amount]" class="form-control purchase-item-discount" value="0"></td>
        <td><input type="number" step="0.01" min="0" name="items[__INDEX__][tax_amount]" class="form-control purchase-item-tax" value="0"></td>
        <td><input type="text" name="items[__INDEX__][remarks]" class="form-control"></td>
        <td><input type="text" class="form-control purchase-item-total" value="0.00" readonly></td>
        <td><button type="button" class="btn btn-sm btn-danger-light purchase-remove-item"><i class="ri-delete-bin-line"></i></button></td>
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
            const paidTotalInput = document.getElementById('paid_total');
            const shippingTotalInput = document.getElementById('shipping_total');
            const subTotalInput = document.getElementById('sub_total');
            const discountTotalInput = document.getElementById('discount_total');
            const taxTotalInput = document.getElementById('tax_total');
            const grandTotalInput = document.getElementById('grand_total');
            const balanceDueInput = document.getElementById('balance_due');

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

            const recalculateRow = (row) => {
                const qty = parseNumber(row.querySelector('.purchase-item-qty')?.value);
                const unitCost = parseNumber(row.querySelector('.purchase-item-cost')?.value);
                const discount = parseNumber(row.querySelector('.purchase-item-discount')?.value);
                const tax = parseNumber(row.querySelector('.purchase-item-tax')?.value);
                const lineTotal = (qty * unitCost) - discount + tax;

                const totalInput = row.querySelector('.purchase-item-total');
                if (totalInput) {
                    totalInput.value = lineTotal.toFixed(2);
                }

                return {
                    sub: qty * unitCost,
                    discount,
                    tax,
                    total: lineTotal,
                };
            };

            const recalculateTotals = () => {
                const rows = body.querySelectorAll('.purchase-item-row');
                let sub = 0;
                let discount = 0;
                let tax = 0;
                let total = 0;

                rows.forEach((row) => {
                    const rowTotals = recalculateRow(row);
                    sub += rowTotals.sub;
                    discount += rowTotals.discount;
                    tax += rowTotals.tax;
                    total += rowTotals.total;
                });

                const shipping = parseNumber(shippingTotalInput?.value);
                const paid = parseNumber(paidTotalInput?.value);
                const grand = total + shipping;

                if (subTotalInput) subTotalInput.value = sub.toFixed(2);
                if (discountTotalInput) discountTotalInput.value = discount.toFixed(2);
                if (taxTotalInput) taxTotalInput.value = tax.toFixed(2);
                if (grandTotalInput) grandTotalInput.value = grand.toFixed(2);
                if (balanceDueInput) balanceDueInput.value = (grand - paid).toFixed(2);
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
                const button = event.target instanceof HTMLElement ? event.target.closest('.purchase-remove-item') : null;
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

            paidTotalInput?.addEventListener('input', recalculateTotals);
            shippingTotalInput?.addEventListener('input', recalculateTotals);

            body.querySelectorAll('.purchase-item-row').forEach((row) => initializeRow(row));
            recalculateTotals();
        });
    </script>
@endpush

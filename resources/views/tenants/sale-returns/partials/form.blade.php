@php
    $formMethod = strtoupper($formMethod ?? 'POST');
    $currentSaleReturn = $saleReturn ?? null;

    $lineItems = old('items');

    if (!is_array($lineItems)) {
        if ($currentSaleReturn) {
            $lineItems = $currentSaleReturn->items
                ->map(
                    fn($item): array => [
                        'sale_item_id' => $item->sale_item_id,
                        'sale_item_label' => $item->saleItem
                            ? ($item->saleItem->sale?->invoice_no ?? '-') . ' - ' . ($item->product?->name ?? '-')
                            : '-',
                        'product_id' => $item->product_id,
                        'tax_id' => $item->tax_id,
                        'sold_qty' => (string) ($item->saleItem?->qty ?? '0'),
                        'returned_qty' => '0',
                        'available_qty' => (string) ($item->saleItem?->qty ?? '0'),
                        'qty' => (string) $item->qty,
                        'unit_price' => (string) $item->unit_price,
                        'tax_amount' => (string) $item->tax_amount,
                        'remarks' => $item->remarks,
                    ],
                )
                ->values()
                ->all();
        } else {
            $lineItems = [
                [
                    'sale_item_id' => '',
                    'sale_item_label' => '-',
                    'product_id' => '',
                    'tax_id' => '',
                    'sold_qty' => '0',
                    'returned_qty' => '0',
                    'available_qty' => '0',
                    'qty' => '1',
                    'unit_price' => '0',
                    'tax_amount' => '0',
                    'remarks' => '',
                ],
            ];
        }
    }

    $autoLoadInvoiceItems = $currentSaleReturn === null && old('items') === null;
    $selectedSaleId = (string) old('sale_id', $currentSaleReturn?->sale_id ?? '');
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

<form method="POST" action="{{ $formAction }}" id="sale-return-form"
    data-invoice-items-url-template="{{ route('tenant.sale-returns.invoice-items', ['sale' => '__SALE_ID__']) }}"
    data-auto-load-invoice-items="{{ $autoLoadInvoiceItems ? '1' : '0' }}">
    @csrf
    @if ($formMethod !== 'POST')
        @method($formMethod)
    @endif

    <div class="row">
        <div class="col-md-3 mb-3">
            <label class="form-label" for="return_no">{{ __('Return No') }}</label>
            <input type="text" name="return_no" id="return_no"
                class="form-control @error('return_no') is-invalid @enderror"
                value="{{ old('return_no', $currentSaleReturn?->return_no) }}" required>
            @error('return_no')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-3 mb-3">
            <label class="form-label" for="return_date">{{ __('Return Date') }}</label>
            <input type="date" name="return_date" id="return_date"
                class="form-control @error('return_date') is-invalid @enderror"
                value="{{ old('return_date', \App\Support\TenantDateTime::format($currentSaleReturn?->return_date ?? now(), 'Y-m-d', '')) }}"
                required>
            @error('return_date')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-3 mb-3">
            <label class="form-label" for="customer_id">{{ __('Customer') }}</label>
            <select name="customer_id" id="customer_id"
                class="form-select singl-select-2 @error('customer_id') is-invalid @enderror">
                <option value="">{{ __('None') }}</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}" @selected(old('customer_id', $currentSaleReturn?->customer_id) === $customer->id)>
                        {{ $customer->name }}
                    </option>
                @endforeach
            </select>
            @error('customer_id')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-3 mb-3">
            <label class="form-label" for="sale_id">{{ __('Sales Invoice') }}</label>
            <select name="sale_id" id="sale_id"
                class="form-select sales-invoice-select2 @error('sale_id') is-invalid @enderror">
                <option value="">{{ __('None') }}</option>
                @foreach ($sales as $sale)
                    <option value="{{ $sale->id }}" @selected(old('sale_id', $currentSaleReturn?->sale_id) === $sale->id)>
                        {{ $sale->invoice_no }}
                    </option>
                @endforeach
            </select>
            @error('sale_id')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-4 mb-3">
            <label class="form-label" for="status">{{ __('Status') }}</label>
            <select name="status" id="status"
                class="form-select singl-select-2 @error('status') is-invalid @enderror" required>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(old('status', $currentSaleReturn?->status?->value ?? 'draft') === $status->value)>
                        {{ ucfirst(str_replace('_', ' ', $status->value)) }}
                    </option>
                @endforeach
            </select>
            @error('status')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-12 mb-3 {{ $selectedSaleId === '' ? 'd-none' : '' }}" id="sale-return-items-section">
            <div class="card border">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="mb-0">{{ __('Return Items') }}</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary"
                        id="add-sale-return-item">{{ __('+ Add Product') }}</button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="min-width:240px;">{{ __('Invoice Line') }}</th>
                                    <th style="min-width:220px;">{{ __('Product') }}</th>
                                    <th style="min-width:110px;">{{ __('Sold Qty') }}</th>
                                    <th style="min-width:130px;">{{ __('Returned Qty') }}</th>
                                    <th style="min-width:130px;">{{ __('Available Qty') }}</th>
                                    <th style="min-width:90px;">{{ __('Return Qty') }}</th>
                                    <th style="min-width:120px;">{{ __('Unit Price') }}</th>
                                    <th style="min-width:120px;">{{ __('Tax Amount') }}</th>
                                    <th style="min-width:160px;">{{ __('Remarks') }}</th>
                                    <th style="min-width:120px;">{{ __('Line Total') }}</th>
                                    <th style="width:70px;"></th>
                                </tr>
                            </thead>
                            <tbody id="sale-return-items-body" data-next-index="{{ count($lineItems) }}">
                                @foreach ($lineItems as $index => $item)
                                    <tr class="sale-return-item-row" data-index="{{ $index }}">
                                        <td>
                                            <input type="hidden" name="items[{{ $index }}][sale_item_id]"
                                                class="sale-return-item-sale-item-id"
                                                value="{{ $item['sale_item_id'] ?? '' }}">
                                            <input type="hidden" name="items[{{ $index }}][tax_id]"
                                                value="{{ $item['tax_id'] ?? '' }}">
                                            <input type="text" class="form-control sale-return-item-label"
                                                value="{{ $item['sale_item_label'] ?? '-' }}" readonly>
                                        </td>
                                        <td>
                                            <select name="items[{{ $index }}][product_id]"
                                                class="form-select singl-select-2 sale-return-item-product" required>
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
                                        <td><input type="text" class="form-control sale-return-item-sold-qty"
                                                value="{{ number_format((float) ($item['sold_qty'] ?? 0), 3, '.', '') }}"
                                                readonly></td>
                                        <td><input type="text" class="form-control sale-return-item-returned-qty"
                                                value="{{ number_format((float) ($item['returned_qty'] ?? 0), 3, '.', '') }}"
                                                readonly></td>
                                        <td><input type="text" class="form-control sale-return-item-available-qty"
                                                value="{{ number_format((float) ($item['available_qty'] ?? 0), 3, '.', '') }}"
                                                readonly></td>
                                        <td><input type="number" step="0.001" min="0.001"
                                                name="items[{{ $index }}][qty]"
                                                class="form-control sale-return-item-qty"
                                                value="{{ $item['qty'] ?? '1' }}" required></td>
                                        <td><input type="number" step="0.01" min="0"
                                                name="items[{{ $index }}][unit_price]"
                                                class="form-control sale-return-item-price"
                                                value="{{ $item['unit_price'] ?? '0' }}" required></td>
                                        <td><input type="number" step="0.01" min="0"
                                                name="items[{{ $index }}][tax_amount]"
                                                class="form-control sale-return-item-tax"
                                                value="{{ $item['tax_amount'] ?? '0' }}"></td>
                                        <td><input type="text" name="items[{{ $index }}][remarks]"
                                                class="form-control" value="{{ $item['remarks'] ?? '' }}"></td>
                                        <td><input type="text" class="form-control sale-return-item-total"
                                                value="0.00" readonly></td>
                                        <td>
                                            <button type="button"
                                                class="btn btn-sm btn-danger-light sale-return-remove-item"><i
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
            <label class="form-label" for="sub_total">{{ __('Sub Total') }}</label>
            <input type="number" step="0.01" name="sub_total" id="sub_total"
                class="form-control @error('sub_total') is-invalid @enderror"
                value="{{ old('sub_total', (string) ($currentSaleReturn?->sub_total ?? '0')) }}" readonly>
            @error('sub_total')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-3 mb-3">
            <label class="form-label" for="tax_total">{{ __('Tax') }}</label>
            <input type="number" step="0.01" name="tax_total" id="tax_total"
                class="form-control @error('tax_total') is-invalid @enderror"
                value="{{ old('tax_total', (string) ($currentSaleReturn?->tax_total ?? '0')) }}" readonly>
            @error('tax_total')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-3 mb-3">
            <label class="form-label" for="grand_total">{{ __('Grand Total') }}</label>
            <input type="number" step="0.01" name="grand_total" id="grand_total"
                class="form-control @error('grand_total') is-invalid @enderror"
                value="{{ old('grand_total', (string) ($currentSaleReturn?->grand_total ?? '0')) }}" readonly>
            @error('grand_total')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-3 mb-3">
            <label class="form-label" for="posted_at">{{ __('Posted At') }}</label>
            <input type="datetime-local" name="posted_at" id="posted_at"
                class="form-control @error('posted_at') is-invalid @enderror"
                value="{{ old('posted_at', \App\Support\TenantDateTime::format($currentSaleReturn?->posted_at, 'Y-m-d\\TH:i', '')) }}">
            @error('posted_at')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-12 mb-3">
            <label class="form-label" for="notes">{{ __('Notes') }}</label>
            <textarea name="notes" id="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $currentSaleReturn?->notes) }}</textarea>
            @error('notes')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
</form>

<template id="sale-return-item-row-template">
    <tr class="sale-return-item-row" data-index="__INDEX__">
        <td>
            <input type="hidden" name="items[__INDEX__][sale_item_id]" class="sale-return-item-sale-item-id"
                value="">
            <input type="hidden" name="items[__INDEX__][tax_id]" value="">
            <input type="text" class="form-control sale-return-item-label" value="-" readonly>
        </td>
        <td>
            <select name="items[__INDEX__][product_id]" class="form-select singl-select-2 sale-return-item-product"
                required>
                <option value="">{{ __('Select product') }}</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }}
                        {{ $product->sku ? '(' . $product->sku . ')' : '' }}</option>
                @endforeach
            </select>
        </td>
        <td><input type="text" class="form-control sale-return-item-sold-qty" value="0.000" readonly></td>
        <td><input type="text" class="form-control sale-return-item-returned-qty" value="0.000" readonly></td>
        <td><input type="text" class="form-control sale-return-item-available-qty" value="0.000" readonly></td>
        <td><input type="number" step="0.001" min="0.001" name="items[__INDEX__][qty]"
                class="form-control sale-return-item-qty" value="1" required></td>
        <td><input type="number" step="0.01" min="0" name="items[__INDEX__][unit_price]"
                class="form-control sale-return-item-price" value="0" required></td>
        <td><input type="number" step="0.01" min="0" name="items[__INDEX__][tax_amount]"
                class="form-control sale-return-item-tax" value="0"></td>
        <td><input type="text" name="items[__INDEX__][remarks]" class="form-control"></td>
        <td><input type="text" class="form-control sale-return-item-total" value="0.00" readonly></td>
        <td><button type="button" class="btn btn-sm btn-danger-light sale-return-remove-item"><i
                    class="ri-delete-bin-line"></i></button></td>
    </tr>
</template>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('sale-return-form');
            const body = document.getElementById('sale-return-items-body');
            if (!form || !body) {
                return;
            }

            const saleSelect = document.getElementById('sale_id');
            const customerSelect = document.getElementById('customer_id');
            const rowTemplate = document.getElementById('sale-return-item-row-template');
            const itemsSection = document.getElementById('sale-return-items-section');
            const addButton = document.getElementById('add-sale-return-item');
            const subTotalInput = document.getElementById('sub_total');
            const taxTotalInput = document.getElementById('tax_total');
            const grandTotalInput = document.getElementById('grand_total');
            const invoiceItemsUrlTemplate = form.dataset.invoiceItemsUrlTemplate ?? '';
            const autoLoadInvoiceItems = form.dataset.autoLoadInvoiceItems === '1';

            const parseNumber = (value) => {
                const parsed = parseFloat(value);
                return Number.isFinite(parsed) ? parsed : 0;
            };

            const toggleItemsSection = (visible) => {
                if (!itemsSection) {
                    return;
                }

                itemsSection.classList.toggle('d-none', !visible);
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

            const setCustomer = (customerId) => {
                if (!(customerSelect instanceof HTMLSelectElement)) {
                    return;
                }

                customerSelect.value = customerId ?? '';
                if (window.jQuery) {
                    window.jQuery(customerSelect).trigger('change.select2');
                }
            };

            const recalculateRow = (row) => {
                const qtyInput = row.querySelector('.sale-return-item-qty');
                const availableQty = parseNumber(row.querySelector('.sale-return-item-available-qty')?.value);
                if (qtyInput instanceof HTMLInputElement && availableQty > 0 && parseNumber(qtyInput.value) >
                    availableQty) {
                    qtyInput.value = String(availableQty);
                }

                const qty = parseNumber(row.querySelector('.sale-return-item-qty')?.value);
                const unitPrice = parseNumber(row.querySelector('.sale-return-item-price')?.value);
                const tax = parseNumber(row.querySelector('.sale-return-item-tax')?.value);
                const lineTotal = (qty * unitPrice) + tax;

                const totalInput = row.querySelector('.sale-return-item-total');
                if (totalInput) {
                    totalInput.value = lineTotal.toFixed(2);
                }

                return {
                    sub: qty * unitPrice,
                    tax,
                    total: lineTotal,
                };
            };

            const recalculateTotals = () => {
                let subTotal = 0;
                let taxTotal = 0;
                let grandTotal = 0;

                body.querySelectorAll('.sale-return-item-row').forEach((row) => {
                    const totals = recalculateRow(row);
                    subTotal += totals.sub;
                    taxTotal += totals.tax;
                    grandTotal += totals.total;
                });

                subTotalInput.value = subTotal.toFixed(2);
                taxTotalInput.value = taxTotal.toFixed(2);
                grandTotalInput.value = grandTotal.toFixed(2);
            };

            const clearRows = () => {
                body.querySelectorAll('.sale-return-item-row').forEach((row) => row.remove());
                body.dataset.nextIndex = '0';
            };

            const addRow = (item = null) => {
                const index = parseInt(body.dataset.nextIndex ?? '0', 10);
                const html = rowTemplate.innerHTML.replaceAll('__INDEX__', String(index));
                body.insertAdjacentHTML('beforeend', html);
                body.dataset.nextIndex = String(index + 1);

                const row = body.querySelector('.sale-return-item-row:last-child');
                if (!row) {
                    return null;
                }

                row.querySelectorAll('.singl-select-2').forEach((element) => initSelect2(element));

                if (item) {
                    const saleItemIdInput = row.querySelector('.sale-return-item-sale-item-id');
                    if (saleItemIdInput instanceof HTMLInputElement) {
                        saleItemIdInput.value = String(item.sale_item_id ?? '');
                    }

                    const labelInput = row.querySelector('.sale-return-item-label');
                    if (labelInput instanceof HTMLInputElement) {
                        labelInput.value = item.line_label ?? '-';
                    }

                    const productSelect = row.querySelector('.sale-return-item-product');
                    if (productSelect instanceof HTMLSelectElement && item.product_id) {
                        productSelect.value = String(item.product_id);
                        if (window.jQuery) {
                            window.jQuery(productSelect).trigger('change.select2');
                        }
                    }

                    const soldQtyInput = row.querySelector('.sale-return-item-sold-qty');
                    if (soldQtyInput instanceof HTMLInputElement) {
                        soldQtyInput.value = Number(parseNumber(item.sold_qty ?? 0)).toFixed(3);
                    }

                    const returnedQtyInput = row.querySelector('.sale-return-item-returned-qty');
                    if (returnedQtyInput instanceof HTMLInputElement) {
                        returnedQtyInput.value = Number(parseNumber(item.returned_qty ?? 0)).toFixed(3);
                    }

                    const availableQtyInput = row.querySelector('.sale-return-item-available-qty');
                    if (availableQtyInput instanceof HTMLInputElement) {
                        const available = parseNumber(item.available_qty ?? 0);
                        availableQtyInput.value = Number(available).toFixed(3);
                    }

                    const qtyInput = row.querySelector('.sale-return-item-qty');
                    if (qtyInput instanceof HTMLInputElement) {
                        const available = parseNumber(item.available_qty ?? 0);
                        qtyInput.value = available > 0 ? Number(available).toFixed(3) : '1';
                        if (available > 0) {
                            qtyInput.max = String(available);
                        }
                    }

                    const unitPriceInput = row.querySelector('.sale-return-item-price');
                    if (unitPriceInput instanceof HTMLInputElement) {
                        unitPriceInput.value = Number(parseNumber(item.unit_price ?? 0)).toFixed(2);
                    }

                    const taxAmountInput = row.querySelector('.sale-return-item-tax');
                    if (taxAmountInput instanceof HTMLInputElement) {
                        taxAmountInput.value = Number(parseNumber(item.tax_amount ?? 0)).toFixed(2);
                    }
                }

                return row;
            };

            const loadInvoiceItems = async (saleId) => {
                if (invoiceItemsUrlTemplate === '') {
                    return;
                }

                const url = invoiceItemsUrlTemplate.replace('__SALE_ID__', encodeURIComponent(String(
                    saleId)));
                if (window.jQuery) {
                    window.jQuery.ajax({
                        url,
                        method: 'GET',
                        dataType: 'json',
                        success: (payload) => {
                            clearRows();

                            if (Array.isArray(payload.items) && payload.items.length > 0) {
                                payload.items.forEach((item) => addRow(item));
                            } else {
                                addRow();
                            }

                            setCustomer(payload.customer_id ?? '');
                            toggleItemsSection(true);
                            recalculateTotals();
                        },
                        error: () => {
                            clearRows();
                            addRow();
                            toggleItemsSection(true);
                            recalculateTotals();
                        },
                    });

                    return;
                }

                const response = await fetch(url, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    clearRows();
                    addRow();
                    toggleItemsSection(true);
                    recalculateTotals();

                    return;
                }

                const payload = await response.json();
                clearRows();

                if (Array.isArray(payload.items) && payload.items.length > 0) {
                    payload.items.forEach((item) => addRow(item));
                } else {
                    addRow();
                }

                setCustomer(payload.customer_id ?? '');
                toggleItemsSection(true);
                recalculateTotals();
            };

            addButton?.addEventListener('click', () => {
                addRow();
                recalculateTotals();
            });

            const handleSaleChange = async () => {
                if (!(saleSelect instanceof HTMLSelectElement)) {
                    return;
                }

                const saleId = saleSelect.value;
                if (saleId === '') {
                    clearRows();
                    toggleItemsSection(false);
                    recalculateTotals();
                    return;
                }

                await loadInvoiceItems(saleId);
            };

            body.addEventListener('input', (event) => {
                const target = event.target;
                if (!(target instanceof HTMLElement)) {
                    return;
                }

                if (!target.closest('.sale-return-item-row')) {
                    return;
                }

                recalculateTotals();
            });

            body.addEventListener('click', (event) => {
                const target = event.target;
                if (!(target instanceof HTMLElement)) {
                    return;
                }

                const removeButton = target.closest('.sale-return-remove-item');
                if (!removeButton) {
                    return;
                }

                removeButton.closest('.sale-return-item-row')?.remove();
                recalculateTotals();
            });

            document.querySelectorAll('.singl-select-2, .sales-invoice-select2').forEach((element) => initSelect2(
                element));
            recalculateTotals();

            if (saleSelect instanceof HTMLSelectElement) {
                if (window.jQuery) {
                    window.jQuery(saleSelect).off('.saleReturnInvoice').on(
                        'change.saleReturnInvoice change.select2.saleReturnInvoice select2:select.saleReturnInvoice select2:clear.saleReturnInvoice select2:unselect.saleReturnInvoice',
                        () => {
                            void handleSaleChange();
                        });
                } else {
                    saleSelect.addEventListener('change', () => {
                        void handleSaleChange();
                    });
                }

                if (autoLoadInvoiceItems && saleSelect.value !== '') {
                    void loadInvoiceItems(saleSelect.value);
                }
            }

            if (saleSelect instanceof HTMLSelectElement && saleSelect.value === '') {
                toggleItemsSection(false);
            }
        });
    </script>
@endpush

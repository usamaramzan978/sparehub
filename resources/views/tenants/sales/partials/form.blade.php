@php
    $formMethod = strtoupper($formMethod ?? 'POST');
    $currentSale = $sale ?? null;

    $lineItems = old('items');

    if (! is_array($lineItems)) {
        if ($currentSale) {
            $lineItems = $currentSale->items->map(fn($item): array => [
                'line_type' => $item->line_type->value,
                'product_id' => $item->product_id,
                'service_catalog_id' => $item->service_catalog_id,
                'mechanic_id' => $item->mechanic_id,
                'description' => $item->description,
                'qty' => (string) $item->qty,
                'unit_price' => (string) $item->unit_price,
                'discount_amount' => (string) $item->discount_amount,
                'tax_amount' => (string) $item->tax_amount,
                'mechanic_charge' => (string) $item->mechanic_charge,
            ])->values()->all();
        } else {
            $lineItems = [[
                'line_type' => 'product',
                'product_id' => '',
                'service_catalog_id' => '',
                'mechanic_id' => '',
                'description' => '',
                'qty' => '1',
                'unit_price' => '0',
                'discount_amount' => '0',
                'tax_amount' => '0',
                'mechanic_charge' => '0',
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
            <label class="form-label" for="invoice_no">{{ __('Invoice No') }}</label>
            <input type="text" name="invoice_no" id="invoice_no"
                class="form-control @error('invoice_no') is-invalid @enderror"
                value="{{ old('invoice_no', $currentSale?->invoice_no) }}" required>
            @error('invoice_no')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-3 mb-3">
            <label class="form-label" for="invoice_date">{{ __('Invoice Date') }}</label>
            <input type="date" name="invoice_date" id="invoice_date"
                class="form-control @error('invoice_date') is-invalid @enderror"
                value="{{ old('invoice_date', $currentSale?->invoice_date?->format('Y-m-d') ?? now()->toDateString()) }}" required>
            @error('invoice_date')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-3 mb-3">
            <label class="form-label" for="customer_id">{{ __('Customer') }}</label>
            <select name="customer_id" id="customer_id"
                class="form-select singl-select-2 @error('customer_id') is-invalid @enderror">
                <option value="">{{ __('Walk-in') }}</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}" @selected(old('customer_id', $currentSale?->customer_id) === $customer->id)>
                        {{ $customer->name }}
                    </option>
                @endforeach
            </select>
            @error('customer_id')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-3 mb-3">
            <label class="form-label" for="job_card_id">{{ __('Job Card') }}</label>
            <select name="job_card_id" id="job_card_id"
                class="form-select singl-select-2 @error('job_card_id') is-invalid @enderror">
                <option value="">{{ __('None') }}</option>
                @foreach ($jobCards as $jobCard)
                    <option value="{{ $jobCard->id }}" @selected(old('job_card_id', $currentSale?->job_card_id) === $jobCard->id)>
                        {{ $jobCard->job_no }}
                    </option>
                @endforeach
            </select>
            @error('job_card_id')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-3 mb-3">
            <label class="form-label" for="invoice_type">{{ __('Invoice Type') }}</label>
            <select name="invoice_type" id="invoice_type"
                class="form-select singl-select-2 @error('invoice_type') is-invalid @enderror" required>
                @foreach ($invoiceTypes as $type)
                    <option value="{{ $type->value }}" @selected(old('invoice_type', $currentSale?->invoice_type?->value ?? 'product') === $type->value)>
                        {{ ucfirst($type->value) }}
                    </option>
                @endforeach
            </select>
            @error('invoice_type')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-3 mb-3">
            <label class="form-label" for="status">{{ __('Status') }}</label>
            <select name="status" id="status"
                class="form-select singl-select-2 @error('status') is-invalid @enderror" required>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(old('status', $currentSale?->status?->value ?? 'draft') === $status->value)>
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
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <h6 class="mb-0">{{ __('Invoice Items') }}</h6>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="add-product-line">{{ __('+ Add Product') }}</button>
                        <button type="button" class="btn btn-sm btn-outline-info" id="add-service-line">{{ __('+ Add Service') }}</button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="min-width: 130px;">{{ __('Type') }}</th>
                                    <th style="min-width: 220px;">{{ __('Product / Service') }}</th>
                                    <th style="min-width: 180px;">{{ __('Description') }}</th>
                                    <th style="min-width: 200px;">{{ __('Mechanic') }}</th>
                                    <th style="min-width: 100px;">{{ __('Qty') }}</th>
                                    <th style="min-width: 120px;">{{ __('Unit Price') }}</th>
                                    <th style="min-width: 120px;">{{ __('Discount') }}</th>
                                    <th style="min-width: 120px;">{{ __('Tax') }}</th>
                                    <th style="min-width: 140px;">{{ __('Mechanic Payable') }}</th>
                                    <th style="min-width: 120px;">{{ __('Line Total') }}</th>
                                    <th style="width: 70px;">{{ __('') }}</th>
                                </tr>
                            </thead>
                            <tbody id="sale-items-body" data-next-index="{{ count($lineItems) }}">
                                @foreach ($lineItems as $index => $item)
                                    @php
                                        $lineType = $item['line_type'] ?? 'product';
                                    @endphp
                                    <tr class="sale-item-row" data-index="{{ $index }}">
                                        <td>
                                            <select name="items[{{ $index }}][line_type]" class="form-select sale-line-type" required>
                                                <option value="product" @selected($lineType === 'product')>{{ __('Product') }}</option>
                                                <option value="service" @selected($lineType === 'service')>{{ __('Service') }}</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="items[{{ $index }}][product_id]"
                                                class="form-select singl-select-2 sale-product-select {{ $lineType === 'product' ? '' : 'd-none' }}"
                                                @disabled($lineType !== 'product')>
                                                <option value="">{{ __('Select Product') }}</option>
                                                @foreach ($products as $product)
                                                    <option value="{{ $product->id }}" @selected(($item['product_id'] ?? '') === $product->id)>
                                                        {{ $product->name }} {{ $product->sku ? '(' . $product->sku . ')' : '' }}
                                                    </option>
                                                @endforeach
                                            </select>

                                            <select name="items[{{ $index }}][service_catalog_id]"
                                                class="form-select singl-select-2 sale-service-select {{ $lineType === 'service' ? '' : 'd-none' }}"
                                                @disabled($lineType !== 'service')>
                                                <option value="">{{ __('Select Service') }}</option>
                                                @foreach ($serviceCatalogs as $service)
                                                    <option value="{{ $service->id }}" @selected(($item['service_catalog_id'] ?? '') === $service->id)>
                                                        {{ $service->name }}
                                                    </option>
                                                @endforeach
                                            </select>

                                            @error("items.$index.product_id")
                                                <span class="text-danger small d-block">{{ $message }}</span>
                                            @enderror
                                            @error("items.$index.service_catalog_id")
                                                <span class="text-danger small d-block">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td>
                                            <input type="text" name="items[{{ $index }}][description]" class="form-control"
                                                value="{{ $item['description'] ?? '' }}" maxlength="200">
                                        </td>
                                        <td>
                                            <select name="items[{{ $index }}][mechanic_id]"
                                                class="form-select singl-select-2 sale-mechanic-select {{ $lineType === 'service' ? '' : 'd-none' }}"
                                                @disabled($lineType !== 'service')>
                                                <option value="">{{ __('Select Mechanic') }}</option>
                                                @foreach ($mechanics as $mechanic)
                                                    <option value="{{ $mechanic->id }}" @selected(($item['mechanic_id'] ?? '') === $mechanic->id)>
                                                        {{ $mechanic->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" step="0.001" min="0.001" name="items[{{ $index }}][qty]"
                                                class="form-control sale-item-qty" value="{{ $item['qty'] ?? '1' }}" required>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0" name="items[{{ $index }}][unit_price]"
                                                class="form-control sale-item-price" value="{{ $item['unit_price'] ?? '0' }}" required>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0" name="items[{{ $index }}][discount_amount]"
                                                class="form-control sale-item-discount" value="{{ $item['discount_amount'] ?? '0' }}">
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0" name="items[{{ $index }}][tax_amount]"
                                                class="form-control sale-item-tax" value="{{ $item['tax_amount'] ?? '0' }}">
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0" name="items[{{ $index }}][mechanic_charge]"
                                                class="form-control sale-item-mechanic-charge {{ $lineType === 'service' ? '' : 'd-none' }}"
                                                value="{{ $item['mechanic_charge'] ?? '0' }}" @disabled($lineType !== 'service')>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control sale-item-total" value="0.00" readonly>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-danger-light sale-remove-item">
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
            @error('items')
                <span class="text-danger small d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-2 mb-3">
            <label class="form-label" for="sub_total">{{ __('Sub Total') }}</label>
            <input type="number" step="0.01" name="sub_total" id="sub_total"
                class="form-control @error('sub_total') is-invalid @enderror"
                value="{{ old('sub_total', (string) ($currentSale?->sub_total ?? '0')) }}" readonly>
            @error('sub_total')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-2 mb-3">
            <label class="form-label" for="discount_total">{{ __('Discount') }}</label>
            <input type="number" step="0.01" name="discount_total" id="discount_total"
                class="form-control @error('discount_total') is-invalid @enderror"
                value="{{ old('discount_total', (string) ($currentSale?->discount_total ?? '0')) }}" readonly>
            @error('discount_total')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-2 mb-3">
            <label class="form-label" for="tax_total">{{ __('Tax') }}</label>
            <input type="number" step="0.01" name="tax_total" id="tax_total"
                class="form-control @error('tax_total') is-invalid @enderror"
                value="{{ old('tax_total', (string) ($currentSale?->tax_total ?? '0')) }}" readonly>
            @error('tax_total')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-2 mb-3">
            <label class="form-label" for="grand_total">{{ __('Grand Total') }}</label>
            <input type="number" step="0.01" name="grand_total" id="grand_total"
                class="form-control @error('grand_total') is-invalid @enderror"
                value="{{ old('grand_total', (string) ($currentSale?->grand_total ?? '0')) }}" readonly>
            @error('grand_total')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-2 mb-3">
            <label class="form-label" for="paid_total">{{ __('Paid') }}</label>
            <input type="number" step="0.01" name="paid_total" id="paid_total"
                class="form-control @error('paid_total') is-invalid @enderror"
                value="{{ old('paid_total', (string) ($currentSale?->paid_total ?? '0')) }}">
            @error('paid_total')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-2 mb-3">
            <label class="form-label" for="balance_due">{{ __('Balance') }}</label>
            <input type="number" step="0.01" name="balance_due" id="balance_due"
                class="form-control @error('balance_due') is-invalid @enderror"
                value="{{ old('balance_due', (string) ($currentSale?->balance_due ?? '0')) }}" readonly>
            @error('balance_due')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-6 mb-3">
            <label class="form-label" for="posted_at">{{ __('Posted At') }}</label>
            <input type="datetime-local" name="posted_at" id="posted_at"
                class="form-control @error('posted_at') is-invalid @enderror"
                value="{{ old('posted_at', $currentSale?->posted_at?->format('Y-m-d\\TH:i')) }}">
            @error('posted_at')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-12 mb-3">
            <label class="form-label" for="notes">{{ __('Notes') }}</label>
            <textarea name="notes" id="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $currentSale?->notes) }}</textarea>
            @error('notes')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <button class="btn btn-primary" type="submit">{{ $submitLabel }}</button>
</form>

<template id="sale-item-row-template">
    <tr class="sale-item-row" data-index="__INDEX__">
        <td>
            <select name="items[__INDEX__][line_type]" class="form-select sale-line-type" required>
                <option value="product" __PRODUCT_SELECTED__>{{ __('Product') }}</option>
                <option value="service" __SERVICE_SELECTED__>{{ __('Service') }}</option>
            </select>
        </td>
        <td>
            <select name="items[__INDEX__][product_id]" class="form-select singl-select-2 sale-product-select __PRODUCT_HIDDEN__" __PRODUCT_DISABLED__>
                <option value="">{{ __('Select Product') }}</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }} {{ $product->sku ? '(' . $product->sku . ')' : '' }}</option>
                @endforeach
            </select>

            <select name="items[__INDEX__][service_catalog_id]" class="form-select singl-select-2 sale-service-select __SERVICE_HIDDEN__" __SERVICE_DISABLED__>
                <option value="">{{ __('Select Service') }}</option>
                @foreach ($serviceCatalogs as $service)
                    <option value="{{ $service->id }}">{{ $service->name }}</option>
                @endforeach
            </select>
        </td>
        <td><input type="text" name="items[__INDEX__][description]" class="form-control" maxlength="200"></td>
        <td>
            <select name="items[__INDEX__][mechanic_id]" class="form-select singl-select-2 sale-mechanic-select __SERVICE_HIDDEN__" __SERVICE_DISABLED__>
                <option value="">{{ __('Select Mechanic') }}</option>
                @foreach ($mechanics as $mechanic)
                    <option value="{{ $mechanic->id }}">{{ $mechanic->name }}</option>
                @endforeach
            </select>
        </td>
        <td><input type="number" step="0.001" min="0.001" name="items[__INDEX__][qty]" class="form-control sale-item-qty" value="1" required></td>
        <td><input type="number" step="0.01" min="0" name="items[__INDEX__][unit_price]" class="form-control sale-item-price" value="0" required></td>
        <td><input type="number" step="0.01" min="0" name="items[__INDEX__][discount_amount]" class="form-control sale-item-discount" value="0"></td>
        <td><input type="number" step="0.01" min="0" name="items[__INDEX__][tax_amount]" class="form-control sale-item-tax" value="0"></td>
        <td><input type="number" step="0.01" min="0" name="items[__INDEX__][mechanic_charge]" class="form-control sale-item-mechanic-charge __SERVICE_HIDDEN__" value="0" __SERVICE_DISABLED__></td>
        <td><input type="text" class="form-control sale-item-total" value="0.00" readonly></td>
        <td>
            <button type="button" class="btn btn-sm btn-danger-light sale-remove-item">
                <i class="ri-delete-bin-line"></i>
            </button>
        </td>
    </tr>
</template>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const body = document.getElementById('sale-items-body');
            if (!body) {
                return;
            }

            const rowTemplate = document.getElementById('sale-item-row-template');
            const addProductButton = document.getElementById('add-product-line');
            const addServiceButton = document.getElementById('add-service-line');
            const paidTotalInput = document.getElementById('paid_total');
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

                $el.select2({
                    width: '100%',
                });
            };

            const setSelectVisibility = (select, visible, clearValue = false) => {
                if (!select) {
                    return;
                }

                select.disabled = !visible;

                if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
                    const $select = window.jQuery(select);
                    const $container = $select.next('.select2');

                    if (clearValue) {
                        $select.val(null).trigger('change.select2');
                    }

                    if ($select.hasClass('select2-hidden-accessible')) {
                        if (visible) {
                            $container.removeClass('d-none').show();
                        } else {
                            $container.addClass('d-none').hide();
                        }
                    } else {
                        if (clearValue) {
                            select.value = '';
                        }
                        select.classList.toggle('d-none', !visible);
                    }
                } else {
                    if (clearValue) {
                        select.value = '';
                    }
                    select.classList.toggle('d-none', !visible);
                }
            };

            const toggleLineTypeFields = (row) => {
                const typeSelect = row.querySelector('.sale-line-type');
                const productSelect = row.querySelector('.sale-product-select');
                const serviceSelect = row.querySelector('.sale-service-select');
                const mechanicSelect = row.querySelector('.sale-mechanic-select');
                const mechanicChargeInput = row.querySelector('.sale-item-mechanic-charge');
                const lineType = typeSelect ? typeSelect.value : 'product';

                if (!productSelect || !serviceSelect) {
                    return;
                }

                if (lineType === 'service') {
                    setSelectVisibility(productSelect, false, true);
                    setSelectVisibility(serviceSelect, true);
                    setSelectVisibility(mechanicSelect, true);
                    if (mechanicChargeInput) {
                        mechanicChargeInput.disabled = false;
                        mechanicChargeInput.classList.remove('d-none');
                    }
                } else {
                    setSelectVisibility(serviceSelect, false, true);
                    setSelectVisibility(productSelect, true);
                    setSelectVisibility(mechanicSelect, false, true);
                    if (mechanicChargeInput) {
                        mechanicChargeInput.value = '0';
                        mechanicChargeInput.disabled = true;
                        mechanicChargeInput.classList.add('d-none');
                    }
                }
            };

            const recalculateRow = (row) => {
                const qtyInput = row.querySelector('.sale-item-qty');
                const priceInput = row.querySelector('.sale-item-price');
                const discountInput = row.querySelector('.sale-item-discount');
                const taxInput = row.querySelector('.sale-item-tax');
                const totalInput = row.querySelector('.sale-item-total');

                const qty = parseNumber(qtyInput?.value);
                const unitPrice = parseNumber(priceInput?.value);
                const discount = parseNumber(discountInput?.value);
                const tax = parseNumber(taxInput?.value);
                const lineTotal = (qty * unitPrice) - discount + tax;

                if (totalInput) {
                    totalInput.value = lineTotal.toFixed(2);
                }

                return {
                    sub: qty * unitPrice,
                    discount,
                    tax,
                    total: lineTotal,
                };
            };

            const recalculateTotals = () => {
                const rows = body.querySelectorAll('.sale-item-row');
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

                if (subTotalInput) {
                    subTotalInput.value = sub.toFixed(2);
                }

                if (discountTotalInput) {
                    discountTotalInput.value = discount.toFixed(2);
                }

                if (taxTotalInput) {
                    taxTotalInput.value = tax.toFixed(2);
                }

                if (grandTotalInput) {
                    grandTotalInput.value = total.toFixed(2);
                }

                if (balanceDueInput) {
                    const paid = parseNumber(paidTotalInput?.value);
                    balanceDueInput.value = (total - paid).toFixed(2);
                }
            };

            const initializeRow = (row) => {
                row.querySelectorAll('.singl-select-2').forEach((select) => {
                    initSelect2(select);
                });

                toggleLineTypeFields(row);
                recalculateRow(row);
            };

            const syncAllRowVisibility = () => {
                body.querySelectorAll('.sale-item-row').forEach((row) => {
                    toggleLineTypeFields(row);
                });
            };

            const addRow = (lineType) => {
                let nextIndex = parseInt(body.dataset.nextIndex ?? '0', 10);
                if (!Number.isFinite(nextIndex)) {
                    nextIndex = body.querySelectorAll('.sale-item-row').length;
                }

                let template = rowTemplate.innerHTML;
                template = template.replaceAll('__INDEX__', String(nextIndex));

                if (lineType === 'service') {
                    template = template
                        .replaceAll('__PRODUCT_SELECTED__', '')
                        .replaceAll('__SERVICE_SELECTED__', 'selected')
                        .replaceAll('__PRODUCT_HIDDEN__', 'd-none')
                        .replaceAll('__SERVICE_HIDDEN__', '')
                        .replaceAll('__PRODUCT_DISABLED__', 'disabled')
                        .replaceAll('__SERVICE_DISABLED__', '');
                } else {
                    template = template
                        .replaceAll('__PRODUCT_SELECTED__', 'selected')
                        .replaceAll('__SERVICE_SELECTED__', '')
                        .replaceAll('__PRODUCT_HIDDEN__', '')
                        .replaceAll('__SERVICE_HIDDEN__', 'd-none')
                        .replaceAll('__PRODUCT_DISABLED__', '')
                        .replaceAll('__SERVICE_DISABLED__', 'disabled');
                }

                body.insertAdjacentHTML('beforeend', template);
                body.dataset.nextIndex = String(nextIndex + 1);

                const row = body.querySelector('.sale-item-row:last-child');
                if (row) {
                    initializeRow(row);
                }

                recalculateTotals();
            };

            addProductButton?.addEventListener('click', () => addRow('product'));
            addServiceButton?.addEventListener('click', () => addRow('service'));

            body.addEventListener('change', (event) => {
                const target = event.target;
                if (!(target instanceof HTMLElement)) {
                    return;
                }

                const row = target.closest('.sale-item-row');
                if (!row) {
                    return;
                }

                if (target.classList.contains('sale-line-type')) {
                    toggleLineTypeFields(row);
                }

                recalculateTotals();
            });

            body.addEventListener('input', (event) => {
                const target = event.target;
                if (!(target instanceof HTMLElement)) {
                    return;
                }

                const row = target.closest('.sale-item-row');
                if (!row) {
                    return;
                }

                recalculateTotals();
            });

            body.addEventListener('click', (event) => {
                const button = event.target instanceof HTMLElement ? event.target.closest('.sale-remove-item') : null;
                if (!button) {
                    return;
                }

                const row = button.closest('.sale-item-row');
                row?.remove();

                if (!body.querySelector('.sale-item-row')) {
                    addRow('product');
                }

                recalculateTotals();
            });

            paidTotalInput?.addEventListener('input', recalculateTotals);

            body.querySelectorAll('.sale-item-row').forEach((row) => initializeRow(row));
            syncAllRowVisibility();
            requestAnimationFrame(syncAllRowVisibility);
            setTimeout(syncAllRowVisibility, 50);
            recalculateTotals();
        });
    </script>
@endpush

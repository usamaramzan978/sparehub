@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [['label' => 'Sales'], ['label' => 'POS']];
    @endphp

    <x-breadcrumb title="POS Screen" :items="$breadcrumbs">
        <x-slot:actions>
            @if ($tenantSettings?->customer_display_enabled)
                <a href="{{ route('tenant.pos.customer-display') }}" class="btn btn-outline-primary" target="_blank"
                    rel="noopener noreferrer">Customer Screen</a>
            @endif
            <a href="{{ route('tenant.sales.index') }}" class="btn btn-outline-secondary">Sales List</a>
        </x-slot:actions>
    </x-breadcrumb>

    <form method="POST" action="{{ route('tenant.pos.store') }}" id="pos-form" enctype="multipart/form-data"
        data-pos-sync-channel="{{ sprintf('sparehub:pos-display:%s:%s', (string) (request()->route('tenant') ?? tenant('id')), (string) session('tenant.current_branch_id')) }}">
        @csrf

        <div class="row g-4">
            <div class="col-12 col-xl-8">
                <div class="card custom-card border-0 shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Scan & Cart</h5>
                    </div>
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <input type="hidden" name="branch_id" value="{{ session('tenant.current_branch_id') }}">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="customer_id">Customer</label>
                                <select name="customer_id" id="customer_id"
                                    class="form-select singl-select-2 @error('customer_id') is-invalid @enderror">
                                    <option value="">Walk-in</option>
                                    @foreach ($customers as $customer)
                                        <option value="{{ $customer->id }}" @selected(old('customer_id') === $customer->id)>
                                            {{ $customer->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('customer_id')
                                    <span class="invalid-feedback text-danger d-block">{{ $message }}</span>
                                @enderror
                                <small id="credit-customer-hint" class="text-danger d-none">Hold sales require a
                                    customer.</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="status">Status</label>
                                <select name="status" id="status"
                                    class="form-select singl-select-2 @error('status') is-invalid @enderror" required
                                    data-pos-status>
                                    @foreach ($statuses as $status)
                                        <option value="{{ $status->value }}" @selected(old('status', 'posted') === $status->value)>
                                            {{ ucfirst($status->value) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('status')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="form-label" for="pos-scan">Scan / Search Product</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="ri-qr-code-line"></i></span>
                                <input type="text" id="pos-scan" class="form-control" autofocus
                                    placeholder="Scan barcode or search SKU/name" data-pos-scan
                                    data-pos-scan-url="{{ route('tenant.pos.scan') }}">
                                <button type="button" class="btn btn-primary" data-pos-add>
                                    Add
                                </button>
                            </div>
                            <div class="list-group mt-2 d-none" data-pos-suggestions></div>
                        </div>
                        @if ($categories->isNotEmpty())
                            <div class="mt-3">
                                <div class="d-flex flex-wrap gap-2" data-pos-categories
                                    data-pos-category-url="{{ route('tenant.pos.catalog') }}">
                                    @foreach ($categories as $category)
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill"
                                            data-catalog-type="product" data-category-id="{{ $category->id }}">
                                            {{ __('Product') }}: {{ $category->name }}
                                        </button>
                                    @endforeach
                                    <button type="button" class="btn btn-sm btn-outline-info rounded-pill"
                                        data-catalog-type="service" data-category-id="all">
                                        {{ __('Services') }}
                                    </button>
                                </div>
                                <div class="row g-3 mt-2" data-pos-catalog-grid>
                                    <div class="col-12 text-muted small" data-pos-catalog-empty>
                                        Select a product category or services to browse items.
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="table-responsive mt-4">
                            <table class="table table-hover align-middle mb-0" data-pos-cart>
                                <thead>
                                    <tr class="text-muted">
                                        <th>Item</th>
                                        <th style="width: 120px;">Stock</th>
                                        <th style="width: 120px;">Qty</th>
                                        <th style="width: 140px;">Price</th>
                                        <th style="width: 140px;">Total</th>
                                        <th class="text-end" style="width: 40px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody data-pos-cart-body>
                                    <tr data-empty>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            Scan a barcode or search to add items.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <button type="button" class="btn btn-outline-danger" data-pos-clear>
                                Clear Cart
                            </button>
                            <button type="button" class="btn btn-outline-secondary" data-pos-hold>
                                Hold Sale
                            </button>
                            <button type="submit" class="btn btn-primary ms-auto" data-pos-submit>
                                Complete Sale
                            </button>
                            <button type="submit" class="btn btn-outline-primary" data-pos-print formtarget="_blank">
                                Complete & Print
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="card custom-card border-0 shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Summary & Payment</h5>
                    </div>
                    <div class="card-body">

                        <div class="mb-4">
                            <label class="form-label d-block">Payment Type</label>
                            <input type="hidden" name="payment_mode" value="{{ old('payment_mode', 'cash') }}"
                                data-pos-payment-mode>
                            <div class="row g-2">
                                @php
                                    $paymentTypeLabels = [
                                        'cash' => [
                                            'label' => 'Cash',
                                            'hint' => 'Cash received',
                                            'icon' => 'ri-cash-line',
                                        ],
                                        'online' => [
                                            'label' => 'Online',
                                            'hint' => 'Bank transfer',
                                            'icon' => 'ri-bank-card-line',
                                        ],
                                        'debit' => [
                                            'label' => 'Loan / Partial',
                                            'hint' => 'Customer owes',
                                            'icon' => 'ri-hand-coin-line',
                                        ],
                                    ];
                                    $paymentTypeCards = [
                                        ['mode' => 'cash', 'method' => 'cash', 'name' => 'Cash'],
                                        ['mode' => 'online', 'method' => 'bank', 'name' => 'Online'],
                                        ['mode' => 'debit', 'method' => 'other', 'name' => 'Debit'],
                                    ];
                                @endphp
                                @foreach ($paymentTypeCards as $paymentTypeCard)
                                    @php
                                        $typeKey = $paymentTypeCard['mode'];
                                        $typeMeta = $paymentTypeLabels[$typeKey] ?? [
                                            'label' => ucfirst($typeKey),
                                            'hint' => '',
                                            'icon' => 'ri-bank-card-line',
                                        ];
                                    @endphp
                                    <div class="col-12 col-sm-4">
                                        <div class="card border-0 shadow-sm payment-mode-card"
                                            data-payment-mode="{{ $typeKey }}"
                                            data-method-id="{{ $paymentTypeCard['method'] }}" style="cursor: pointer;">
                                            <div class="card-body d-flex align-items-center gap-2 py-3">
                                                <i class="{{ $typeMeta['icon'] }} fs-5 text-primary"></i>
                                                <div>
                                                    <div class="fw-semibold">{{ __($paymentTypeCard['name']) }}</div>
                                                    <div class="text-muted small">{{ $typeMeta['label'] }}
                                                        {{ $typeMeta['hint'] ? '· ' . $typeMeta['hint'] : '' }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="row g-3 mb-3 d-none" data-pos-online-proof-row>
                            <div class="col-12">
                                <label class="form-label" for="payment_proof">{{ __('Online Payment Proof') }} <span
                                        class="text-danger">*</span></label>
                                <input type="file" name="payment_proof" id="payment_proof" accept="image/*"
                                    class="form-control @error('payment_proof') is-invalid @enderror"
                                    data-pos-online-proof-input>
                                <small class="text-muted">{{ __('Upload screenshot/receipt (jpg, png, webp).') }}</small>
                                @error('payment_proof')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="row g-3 mt-3">
                            <div class="col-12">
                                <label class="form-label" for="pos-discount-type">Discount Type</label>
                                <select id="pos-discount-type" name="discount_type"
                                    class="form-select singl-select-2 @error('discount_type') is-invalid @enderror"
                                    data-pos-discount-type>
                                    <option value="amount" @selected(old('discount_type', 'amount') === 'amount')>Amount</option>
                                    <option value="percent" @selected(old('discount_type') === 'percent')>Percent</option>
                                </select>
                                @error('discount_type')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="pos-discount-value">Discount</label>
                                <input type="number" step="0.01" id="pos-discount-value" name="discount_value"
                                    class="form-control @error('discount_value') is-invalid @enderror"
                                    value="{{ old('discount_value', 0) }}" data-pos-discount-value>
                                @error('discount_value')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <hr>

                        <div class="my-3 d-none" data-payments data-payments-index="1">
                            <div class="d-flex align-items-center justify-content-between">
                                <h6 class="fw-semibold mb-0">Payments</h6>
                            </div>
                            <div class="mt-3" data-payments-list>
                                <div class="row align-items-end mb-3" data-payment-row>
                                    <div class="col-md-4 d-none">
                                        <label class="form-label" for="pos-payment-method-0">Method</label>
                                        <select name="payments[0][method_id]" id="pos-payment-method-0"
                                            class="form-select singl-select-2">
                                            @foreach ($paymentMethods as $method)
                                                <option value="{{ $method->value }}" @selected(old('payments.0.method_id', 'cash') === $method->value)>
                                                    {{ ucfirst($method->value) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-12" data-payment-amount-row>
                                        <label class="form-label" for="pos-payment-amount-0">Amount</label>
                                        <input type="number" step="0.01" name="payments[0][amount]"
                                            id="pos-payment-amount-0" class="form-control"
                                            value="{{ old('payments.0.amount', 0) }}">
                                    </div>

                                    <div class="col-md-3 d-none">
                                        <label class="form-label" for="pos-payment-status-0">Status</label>
                                        <select name="payments[0][status]" id="pos-payment-status-0"
                                            class="form-select singl-select-2">
                                            <option value="paid" @selected(old('payments.0.status', 'paid') === 'paid')>{{ __('Paid') }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-3 mt-3" data-pos-cash-row>
                                <div class="col-12">
                                    <label class="form-label" for="pos-cash-received">Cash Received</label>
                                    <input type="number" step="0.01" id="pos-cash-received" class="form-control"
                                        value="{{ old('cash_received', 0) }}" data-pos-cash-received>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Change Due</label>
                                    <div class="form-control-plaintext fw-semibold" data-pos-change>0.00</div>
                                </div>
                                <div class="col-12 d-none" data-pos-short-row>
                                    <label class="form-label">Balance Due</label>
                                    <div class="form-control-plaintext fw-semibold" data-pos-short>0.00</div>
                                </div>
                            </div>
                            <div class="row g-3 mt-2 d-none" data-pos-total-row>
                                <div class="col-12">
                                    <label class="form-label">Total</label>
                                    <div class="form-control-plaintext fw-semibold" data-pos-total-inline>0.00</div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="border rounded p-3 bg-light">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Subtotal</span>
                                <span class="fw-semibold" data-pos-subtotal>0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Discount</span>
                                <span class="fw-semibold" data-pos-discount>0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Tax</span>
                                <span class="fw-semibold" data-pos-tax>0.00</span>
                            </div>
                            <div class="d-flex justify-content-between border-top pt-2">
                                <span class="text-muted">Total</span>
                                <span class="fw-bold" data-pos-total>0.00</span>
                            </div>
                        </div>

                        <div class="mt-3 text-muted small">
                            Payment is optional for draft/hold sales. For posted sales, make sure payment covers the total.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" name="auto_invoice" value="1">
        <input type="hidden" name="print_receipt" value="0" data-pos-print-flag>
        <input type="hidden" name="cash_received" value="{{ old('cash_received', 0) }}" data-pos-cash-hidden>
        <input type="hidden" name="change_due" value="{{ old('change_due', 0) }}" data-pos-change-hidden>
        <div data-pos-hidden-items></div>
    </form>
@endsection

@push('scripts')
    <script>
        (function() {
            const scanInput = document.querySelector('[data-pos-scan]');
            const scanUrl = scanInput?.dataset.posScanUrl;
            const addBtn = document.querySelector('[data-pos-add]');
            const suggestionBox = document.querySelector('[data-pos-suggestions]');
            const cartBody = document.querySelector('[data-pos-cart-body]');
            const emptyRow = cartBody?.querySelector('[data-empty]');
            const hiddenItems = document.querySelector('[data-pos-hidden-items]');
            const discountType = document.querySelector('[data-pos-discount-type]');
            const discountValue = document.querySelector('[data-pos-discount-value]');
            const totalEl = document.querySelector('[data-pos-total]');
            const subtotalEl = document.querySelector('[data-pos-subtotal]');
            const discountEl = document.querySelector('[data-pos-discount]');
            const taxEl = document.querySelector('[data-pos-tax]');
            const clearBtn = document.querySelector('[data-pos-clear]');
            const statusSelect = document.querySelector('[data-pos-status]');
            const holdBtn = document.querySelector('[data-pos-hold]');
            const printBtn = document.querySelector('[data-pos-print]');
            const submitBtn = document.querySelector('[data-pos-submit]');
            const posForm = document.getElementById('pos-form');
            const printFlag = document.querySelector('[data-pos-print-flag]');
            const syncChannelName = posForm?.dataset.posSyncChannel || '';
            const syncBroadcaster = 'BroadcastChannel' in window && syncChannelName !== '' ?
                new BroadcastChannel(syncChannelName) :
                null;
            const cashInput = document.querySelector('[data-pos-cash-received]');
            const changeEl = document.querySelector('[data-pos-change]');
            const cashHidden = document.querySelector('[data-pos-cash-hidden]');
            const changeHidden = document.querySelector('[data-pos-change-hidden]');
            const paymentModeInput = document.querySelector('[data-pos-payment-mode]');
            const paymentModeCards = document.querySelectorAll('[data-payment-mode]');
            const onlineProofRow = document.querySelector('[data-pos-online-proof-row]');
            const onlineProofInput = document.querySelector('[data-pos-online-proof-input]');
            const cashRow = document.querySelector('[data-pos-cash-row]');
            const totalRow = document.querySelector('[data-pos-total-row]');
            const totalInlineEl = document.querySelector('[data-pos-total-inline]');
            const shortRow = document.querySelector('[data-pos-short-row]');
            const shortEl = document.querySelector('[data-pos-short]');
            const categoryWrap = document.querySelector('[data-pos-categories]');
            const categoryUrlTemplate = categoryWrap?.dataset.posCategoryUrl;
            const catalogGrid = document.querySelector('[data-pos-catalog-grid]');
            const catalogEmpty = catalogGrid?.querySelector('[data-pos-catalog-empty]');

            const paymentsRoot = document.querySelector('[data-payments]');
            const paymentsList = paymentsRoot?.querySelector('[data-payments-list]');
            const paymentTemplate = paymentsRoot?.querySelector('[data-payment-template]');
            const paymentsTotal = paymentsRoot?.querySelector('[data-payments-total]');
            const paymentAmountRow = paymentsList?.querySelector('[data-payment-amount-row]');
            let paymentsIndex = paymentsRoot ? parseInt(paymentsRoot.dataset.paymentsIndex || '1', 10) : 1;
            let paymentTouched = false;
            let cashTouched = false;
            let currentPaymentMode = paymentModeInput?.value || 'cash';
            let catalogItems = [];
            const initialState = {
                hasOldInput: @json(session()->hasOldInput()),
                items: @json(old('items', [])),
                paymentMode: @json(old('payment_mode', 'cash')),
                status: @json(old('status')),
                cashReceived: @json(old('cash_received')),
                paymentAmount: @json(old('payments.0.amount')),
                paymentMethod: @json(old('payments.0.method_id')),
                paymentStatus: @json(old('payments.0.status')),
            };

            const cart = [];

            const money = (value) => (Number.isFinite(value) ? value.toFixed(2) : '0.00');
            const stockCount = (value) => (Number.isFinite(value) ? Math.round(value).toString() : '0');
            const paymentAmountInput = () => paymentsList?.querySelector('input[name^="payments"][name$="[amount]"]');

            const syncPosDisplay = () => {
                if (syncChannelName === '') {
                    return;
                }

                const total = parseFloat(totalEl?.textContent || '0');
                const cashValue = parseFloat(cashInput?.value || '0');
                const paid = currentPaymentMode === 'debit' ?
                    Math.min(cashValue, total) :
                    total;
                const changeDue = currentPaymentMode === 'cash' ? Math.max(cashValue - total, 0) : 0;
                const balanceDue = Math.max(total - paid, 0);
                const snapshot = {
                    items: cart.map((item) => ({
                        name: item.name,
                        sku: item.sku,
                        type: item.type,
                        qty: item.qty,
                        price: item.price,
                        total: item.qty * item.price,
                    })),
                    subtotal: parseFloat(subtotalEl?.textContent || '0'),
                    discount: parseFloat(discountEl?.textContent || '0'),
                    tax: parseFloat(taxEl?.textContent || '0'),
                    total,
                    paid,
                    change_due: changeDue,
                    balance_due: balanceDue,
                    payment_mode: currentPaymentMode,
                    status: statusSelect?.value || 'posted',
                    updated_at: new Intl.DateTimeFormat(undefined, {
                        hour: 'numeric',
                        minute: '2-digit',
                        second: '2-digit',
                    }).format(new Date()),
                };

                try {
                    window.localStorage.setItem(syncChannelName, JSON.stringify(snapshot));
                } catch (error) {
                }

                syncBroadcaster?.postMessage(snapshot);
            };

            const normalizeOldCartItem = (item, index) => {
                const lineType = item.type === 'service' ? 'service' : 'product';
                const qty = Math.max(parseFloat(item.qty || '1'), 1);
                const price = Math.max(parseFloat(item.price || '0'), 0);
                const taxRate = Math.max(parseFloat(item.tax_rate || '0'), 0);
                const taxInclusiveRaw = item.tax_inclusive;

                return {
                    id: `${lineType}:${item.ref_id ?? index}`,
                    ref_id: item.ref_id ?? '',
                    type: lineType,
                    name: item.name || (lineType === 'service' ? 'Service Item' : 'Product Item'),
                    sku: item.sku || '',
                    price,
                    tax_rate: taxRate,
                    tax_inclusive: taxInclusiveRaw === true || taxInclusiveRaw === 1 || taxInclusiveRaw === '1',
                    stock: Number(item.stock || 0),
                    qty,
                };
            };

            const setPaymentMethod = (mode, methodId, options = {}) => {
                const {
                    syncStatus = true
                } = options;
                currentPaymentMode = mode;
                if (paymentModeInput) {
                    paymentModeInput.value = mode;
                }

                paymentModeCards.forEach((card) => {
                    const isActive = card.dataset.paymentMode === mode && card.dataset.methodId ===
                        methodId;
                    card.classList.toggle('border-primary', isActive);
                    card.classList.toggle('border-0', !isActive);
                });

                const paymentsBlock = paymentsRoot;

                if (mode === 'cash') {
                    onlineProofRow?.classList.add('d-none');
                    if (onlineProofInput) {
                        onlineProofInput.value = '';
                    }
                    paymentTouched = false;
                    cashTouched = false;
                    paymentsBlock?.classList.remove('d-none');
                    cashRow?.classList.remove('d-none');
                    totalRow?.classList.add('d-none');
                    shortRow?.classList.add('d-none');
                    if (shortEl) {
                        shortEl.textContent = '0.00';
                    }
                    paymentAmountRow?.classList.remove('d-none');
                    paymentsList?.querySelectorAll('input, select').forEach((el) => {
                        el.disabled = false;
                    });
                    if (syncStatus && statusSelect) {
                        statusSelect.value = 'posted';
                    }
                    const firstMethod = paymentsList?.querySelector(
                        'select[name^="payments"][name$="[method_id]"]');
                    const firstAmount = paymentsList?.querySelector('input[name^="payments"][name$="[amount]"]');
                    const firstStatus = paymentsList?.querySelector('select[name^="payments"][name$="[status]"]');

                    if (firstMethod instanceof HTMLSelectElement && methodId) {
                        firstMethod.value = methodId;
                    }
                    if (firstAmount instanceof HTMLInputElement) {
                        firstAmount.value = money(parseFloat(totalEl?.textContent || '0'));
                    }
                    if (firstStatus instanceof HTMLSelectElement) {
                        firstStatus.value = 'paid';
                    }
                    calculateTotals();
                    return;
                }

                if (mode === 'online') {
                    onlineProofRow?.classList.remove('d-none');
                    paymentTouched = false;
                    cashTouched = false;
                    paymentsBlock?.classList.add('d-none');
                    cashRow?.classList.add('d-none');
                    totalRow?.classList.add('d-none');
                    shortRow?.classList.add('d-none');
                    if (shortEl) {
                        shortEl.textContent = '0.00';
                    }
                    paymentAmountRow?.classList.remove('d-none');
                    paymentsList?.querySelectorAll('input, select').forEach((el) => {
                        el.disabled = false;
                    });
                    if (syncStatus && statusSelect) {
                        statusSelect.value = 'posted';
                    }

                    const firstMethod = paymentsList?.querySelector(
                        'select[name^="payments"][name$="[method_id]"]');
                    const firstAmount = paymentsList?.querySelector('input[name^="payments"][name$="[amount]"]');
                    const firstStatus = paymentsList?.querySelector('select[name^="payments"][name$="[status]"]');

                    if (firstMethod instanceof HTMLSelectElement && methodId) {
                        firstMethod.value = methodId;
                    }
                    if (firstAmount instanceof HTMLInputElement) {
                        firstAmount.value = money(parseFloat(totalEl?.textContent || '0'));
                    }
                    if (firstStatus instanceof HTMLSelectElement) {
                        firstStatus.value = 'paid';
                    }
                    updatePaymentsTotal();
                    calculateTotals();
                    return;
                }

                paymentTouched = false;
                cashTouched = false;
                onlineProofRow?.classList.add('d-none');
                if (onlineProofInput) {
                    onlineProofInput.value = '';
                }
                paymentsBlock?.classList.remove('d-none');
                cashRow?.classList.remove('d-none');
                totalRow?.classList.remove('d-none');
                shortRow?.classList.remove('d-none');
                paymentAmountRow?.classList.add('d-none');
                paymentsList?.querySelectorAll('input, select').forEach((el) => {
                    el.disabled = false;
                });
                if (syncStatus && statusSelect) {
                    statusSelect.value = 'hold';
                }
                const firstMethod = paymentsList?.querySelector('select[name^="payments"][name$="[method_id]"]');
                if (firstMethod instanceof HTMLSelectElement && methodId) {
                    firstMethod.value = methodId;
                }
                const firstAmount = paymentsList?.querySelector('input[name^="payments"][name$="[amount]"]');
                if (firstAmount instanceof HTMLInputElement) {
                    firstAmount.value = cashInput?.value || '0';
                }
                calculateTotals();
            };

            const buildHiddenInputs = () => {
                hiddenItems.innerHTML = '';
                cart.forEach((item, index) => {
                    hiddenItems.insertAdjacentHTML('beforeend', `
                        <input type="hidden" name="items[${index}][type]" value="${item.type}">
                        <input type="hidden" name="items[${index}][ref_id]" value="${item.ref_id}">
                        <input type="hidden" name="items[${index}][qty]" value="${item.qty}">
                        <input type="hidden" name="items[${index}][price]" value="${item.price}">
                        <input type="hidden" name="items[${index}][tax_rate]" value="${item.tax_rate}">
                        <input type="hidden" name="items[${index}][tax_inclusive]" value="${item.tax_inclusive ? 1 : 0}">
                        <input type="hidden" name="items[${index}][name]" value="${item.name}">
                    `);
                });
            };

            const updatePaymentsTotal = () => {
                if (!paymentsTotal || !paymentsList) {
                    return;
                }
                let total = 0;
                paymentsList.querySelectorAll('input[name^="payments"][name$="[amount]"]').forEach((input) => {
                    const value = parseFloat(input.value || '0');
                    if (!Number.isNaN(value)) {
                        total += value;
                    }
                });
                paymentsTotal.textContent = total.toFixed(2);
            };

            const initSelect2 = (container) => {
                if (!window.$ || !window.$.fn?.select2) {
                    return;
                }
                window.$(container)
                    .find('.singl-select-2')
                    .each(function() {
                        const $el = window.$(this);
                        if ($el.hasClass('select2-hidden-accessible')) {
                            return;
                        }
                        $el.select2({
                            width: '100%'
                        });
                    });
            };

            const calculateTotals = () => {
                const subtotal = cart.reduce((sum, item) => sum + item.qty * item.price, 0);
                const discountVal = parseFloat(discountValue.value || '0');
                const discount = discountType.value === 'percent' ?
                    (subtotal * discountVal) / 100 :
                    discountVal;
                const discountRatio = subtotal > 0 ? (discount / subtotal) : 0;
                let tax = 0;
                let total = 0;

                cart.forEach((item) => {
                    const lineSubtotal = item.qty * item.price;
                    const lineNet = Math.max(lineSubtotal - (lineSubtotal * discountRatio), 0);
                    const rate = parseFloat(item.tax_rate || '0');
                    const inclusive = Boolean(item.tax_inclusive);
                    let lineTax = 0;

                    if (rate > 0) {
                        lineTax = inclusive ? (lineNet - (lineNet / (1 + rate / 100))) : (lineNet * rate) /
                            100;
                    }

                    tax += lineTax;
                    total += lineNet + (inclusive ? 0 : lineTax);
                });

                subtotalEl.textContent = money(subtotal);
                discountEl.textContent = money(discount);
                taxEl.textContent = money(tax);
                totalEl.textContent = money(total);

                if (!cashTouched) {
                    if (currentPaymentMode === 'cash') {
                        cashInput.value = money(total);
                    } else {
                        cashInput.value = '0.00';
                    }
                }
                const cashValue = parseFloat(cashInput.value || '0');
                const change = currentPaymentMode === 'cash' ?
                    Math.abs(cashValue - total) :
                    Math.max(cashValue - total, 0);
                changeEl.textContent = money(change);
                cashHidden.value = money(cashValue);
                changeHidden.value = money(change);
                if (shortEl) {
                    const short = Math.max(total - cashValue, 0);
                    shortEl.textContent = money(short);
                    if (shortRow) {
                        if (currentPaymentMode === 'cash') {
                            shortRow.classList.add('d-none');
                        } else if (currentPaymentMode === 'debit') {
                            shortRow.classList.remove('d-none');
                        } else {
                            shortRow.classList.add('d-none');
                        }
                    }
                }

                if (!paymentTouched && paymentsList) {
                    const firstAmount = paymentsList.querySelector('input[name^="payments"][name$="[amount]"]');
                    if (firstAmount instanceof HTMLInputElement) {
                        firstAmount.value = currentPaymentMode === 'debit' ?
                            money(cashValue) :
                            money(total);
                    }
                }

                if (totalInlineEl) {
                    totalInlineEl.textContent = money(total);
                }

                buildHiddenInputs();
                updatePaymentsTotal();
                syncPosDisplay();
            };

            const renderCart = () => {
                cartBody.innerHTML = '';
                if (cart.length === 0) {
                    cartBody.appendChild(emptyRow);
                    calculateTotals();
                    return;
                }

                cart.forEach((item, index) => {
                    const row = document.createElement('tr');
                    const isService = item.type === 'service';
                    row.innerHTML = `
                        <td>
                            <div class="fw-semibold">${item.name}
                                <span class="badge ${isService ? 'bg-secondary' : 'bg-primary'}">
                                    ${isService ? 'Service' : 'Product'}
                                </span>
                            </div>
                            <div class="text-muted small d-flex align-items-center gap-2">
                                <span>${item.sku ?? ''}</span>
                            </div>
                        </td>
                        <td>
                            ${
                                isService
                                    ? '<span class="text-muted small">N/A</span>'
                                    : `<span class="badge bg-info">${stockCount(item.stock)}</span>`
                            }
                        </td>
                        <td>
                            ${
                                isService
                                    ? '<span class="text-muted small">N/A</span>'
                                    : `<input type="number" step="1" min="1" class="form-control form-control-sm"
                                                    value="${item.qty}" data-pos-qty="${index}">`
                            }
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" class="form-control form-control-sm"
                                value="${item.price}" data-pos-price="${index}">
                        </td>
                        <td class="fw-semibold">${money(item.qty * item.price)}</td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-danger" data-pos-remove="${index}">
                                <i class="ri-close-line"></i>
                            </button>
                        </td>
                    `;
                    cartBody.appendChild(row);
                });

                initSelect2(cartBody);
                calculateTotals();
            };

            const addItem = (item) => {
                const existing = cart.find((entry) => entry.id === item.id);
                if (existing) {
                    if (existing.type === 'product') {
                        existing.qty += 1;
                    }
                } else {
                    cart.push({
                        id: item.id,
                        ref_id: item.ref_id,
                        type: item.type,
                        name: item.name,
                        sku: item.sku,
                        price: Number(item.price || 0),
                        tax_rate: Number(item.tax_rate || 0),
                        tax_inclusive: Boolean(item.tax_inclusive),
                        stock: Number(item.stock || 0),
                        qty: 1,
                    });
                }
                renderCart();
            };

            const setActiveCategory = (btn) => {
                if (!categoryWrap) {
                    return;
                }
                categoryWrap.querySelectorAll('button').forEach((button) => {
                    const isActive = button === btn;
                    button.classList.toggle('btn-primary', isActive);
                    button.classList.toggle('btn-outline-primary', !isActive);
                });
            };

            const renderCatalog = (items) => {
                if (!catalogGrid) {
                    return;
                }
                catalogItems = items;
                catalogGrid.innerHTML = '';
                if (!items.length) {
                    const empty = document.createElement('div');
                    empty.className = 'col-12 text-muted small';
                    empty.textContent = 'No items found.';
                    catalogGrid.appendChild(empty);
                    return;
                }

                items.forEach((item, index) => {
                    const isService = item.type === 'service';
                    const col = document.createElement('div');
                    col.className = 'col-12 col-sm-6 col-lg-3';
                    col.innerHTML = `
                        <div class="card h-100 border-0 shadow-sm bg-body">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-semibold">${item.name}</div>
                                        <div class="text-muted small">${item.product_name ?? ''}</div>
                                    </div>
                                    ${
                                        isService
                                            ? '<span class="badge bg-secondary">Service</span>'
                                            : `<span class="badge bg-info">${stockCount(Number(item.stock || 0))}</span>`
                                    }
                                </div>
                                <div class="mt-2 text-muted small">${item.sku ?? ''}</div>
                                <div class="mt-auto d-flex align-items-center justify-content-between pt-3">
                                    <div class="fw-semibold text-primary">${money(Number(item.price || 0))}</div>
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                        data-catalog-add="${index}">
                                        Add
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                    catalogGrid.appendChild(col);
                });
            };

            const clearSuggestions = () => {
                suggestionBox.classList.add('d-none');
                suggestionBox.innerHTML = '';
            };

            const handleScan = async () => {
                const query = scanInput.value.trim();
                if (!query || !scanUrl) return;

                const response = await fetch(`${scanUrl}?query=${encodeURIComponent(query)}`, {
                    headers: {
                        'Accept': 'application/json'
                    },
                });

                if (!response.ok) {
                    clearSuggestions();
                    return;
                }

                const data = await response.json();
                if (data.mode === 'single') {
                    addItem(data.item);
                    scanInput.value = '';
                    clearSuggestions();
                    return;
                }

                if (data.mode === 'list') {
                    suggestionBox.classList.remove('d-none');
                    suggestionBox.innerHTML = '';
                    data.items.forEach((item) => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'list-group-item list-group-item-action';
                        btn.textContent = `${item.name} (${item.sku ?? ''})`;
                        btn.addEventListener('click', () => {
                            addItem(item);
                            scanInput.value = '';
                            clearSuggestions();
                        });
                        suggestionBox.appendChild(btn);
                    });
                }
            };

            let searchTimer = null;
            const handleSearchInput = () => {
                const query = scanInput.value.trim();
                if (searchTimer) {
                    clearTimeout(searchTimer);
                }
                if (query.length < 2) {
                    clearSuggestions();
                    return;
                }
                searchTimer = setTimeout(async () => {
                    const response = await fetch(`${scanUrl}?query=${encodeURIComponent(query)}`, {
                        headers: {
                            'Accept': 'application/json'
                        },
                    });

                    if (!response.ok) {
                        clearSuggestions();
                        return;
                    }

                    const data = await response.json();
                    suggestionBox.classList.remove('d-none');
                    suggestionBox.innerHTML = '';

                    if (data.mode === 'single') {
                        const item = data.item;
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className =
                            'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
                        btn.innerHTML = `
                            <span>${item.name} (${item.sku ?? ''})</span>
                            <span class="badge bg-primary">Add</span>
                        `;
                        btn.addEventListener('click', () => {
                            addItem(item);
                            scanInput.value = '';
                            clearSuggestions();
                        });
                        suggestionBox.appendChild(btn);
                        return;
                    }

                    if (data.mode === 'list') {
                        data.items.forEach((item) => {
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'list-group-item list-group-item-action';
                            btn.textContent = `${item.name} (${item.sku ?? ''})`;
                            btn.addEventListener('click', () => {
                                addItem(item);
                                scanInput.value = '';
                                clearSuggestions();
                            });
                            suggestionBox.appendChild(btn);
                        });
                        return;
                    }

                    clearSuggestions();
                }, 250);
            };

            addBtn?.addEventListener('click', handleScan);
            scanInput?.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    handleScan();
                }
            });
            scanInput?.addEventListener('input', handleSearchInput);

            cartBody?.addEventListener('input', (event) => {
                const qtyIndex = event.target.getAttribute('data-pos-qty');
                const priceIndex = event.target.getAttribute('data-pos-price');
                let shouldRender = false;
                if (qtyIndex !== null) {
                    const idx = Number(qtyIndex);
                    cart[idx].qty = Math.max(parseFloat(event.target.value || '1'), 1);
                    shouldRender = true;
                }
                if (priceIndex !== null) {
                    const idx = Number(priceIndex);
                    cart[idx].price = Math.max(parseFloat(event.target.value || '0'), 0);
                    shouldRender = true;
                }
                if (shouldRender) {
                    renderCart();
                }
            });

            cartBody?.addEventListener('click', (event) => {
                const btn = event.target.closest('[data-pos-remove]');
                if (!btn) return;
                const index = Number(btn.getAttribute('data-pos-remove'));
                cart.splice(index, 1);
                renderCart();
            });

            [discountType, discountValue].forEach((el) => {
                el?.addEventListener('input', calculateTotals);
            });

            cashInput?.addEventListener('input', () => {
                cashTouched = true;
                if (currentPaymentMode === 'debit' && paymentsList) {
                    const firstAmount = paymentAmountInput();
                    if (firstAmount instanceof HTMLInputElement) {
                        firstAmount.value = cashInput.value || '0';
                        paymentTouched = true;
                    }
                    updatePaymentsTotal();
                }
                calculateTotals();
            });
            cashInput?.addEventListener('change', () => {
                cashTouched = true;
                if (currentPaymentMode === 'debit' && paymentsList) {
                    const firstAmount = paymentAmountInput();
                    if (firstAmount instanceof HTMLInputElement) {
                        firstAmount.value = cashInput.value || '0';
                        paymentTouched = true;
                    }
                    updatePaymentsTotal();
                }
                calculateTotals();
            });

            if (paymentsRoot && paymentsList) {
                paymentsList.addEventListener('input', (event) => {
                    const target = event.target;
                    if (target instanceof HTMLInputElement && target.name.endsWith('[amount]')) {
                        paymentTouched = true;
                        updatePaymentsTotal();
                        calculateTotals();
                    }
                });

                initSelect2(paymentsList);
                updatePaymentsTotal();
            }

            clearBtn?.addEventListener('click', () => {
                cart.splice(0, cart.length);
                renderCart();
            });

            holdBtn?.addEventListener('click', () => {
                if (statusSelect) {
                    statusSelect.value = 'draft';
                }
                paymentsList?.querySelectorAll('input, select').forEach((el) => {
                    el.disabled = true;
                });
                paymentTouched = true;
                buildHiddenInputs();
                syncPosDisplay();
                document.getElementById('pos-form')?.submit();
            });

            submitBtn?.addEventListener('click', () => {
                if (printFlag) {
                    printFlag.value = '0';
                }
            });

            printBtn?.addEventListener('click', () => {
                if (printFlag) {
                    printFlag.value = '1';
                }
            });

            statusSelect?.addEventListener('change', () => {
                const hint = document.getElementById('credit-customer-hint');
                if (!hint) return;
                hint.classList.toggle('d-none', statusSelect.value !== 'hold');
                syncPosDisplay();
            });

            posForm?.addEventListener('submit', () => {
                buildHiddenInputs();
            });

            const loadCategoryVariants = async (categoryId, type, btn = null) => {
                if (!categoryUrlTemplate || !categoryId || !type) {
                    return;
                }

                if (btn) {
                    setActiveCategory(btn);
                }
                if (catalogGrid) {
                    catalogGrid.innerHTML =
                        '<div class="col-12 text-muted small">Loading items...</div>';
                }

                const params = new URLSearchParams({
                    type,
                    category: categoryId,
                });
                const response = await fetch(`${categoryUrlTemplate}?${params.toString()}`, {
                    headers: {
                        'Accept': 'application/json'
                    },
                });
                if (!response.ok) {
                    renderCatalog([]);
                    return;
                }
                const data = await response.json();
                renderCatalog(Array.isArray(data.items) ? data.items : []);
            };

            categoryWrap?.addEventListener('click', async (event) => {
                const btn = event.target.closest('[data-category-id]');
                if (!btn) return;
                const categoryId = btn.dataset.categoryId;
                const type = btn.dataset.catalogType;
                await loadCategoryVariants(categoryId, type, btn);
            });

            catalogGrid?.addEventListener('click', (event) => {
                const btn = event.target.closest('[data-catalog-add]');
                if (!btn) return;
                const index = Number(btn.getAttribute('data-catalog-add'));
                const item = catalogItems[index];
                if (item) {
                    addItem(item);
                }
            });

            paymentModeCards.forEach((card) => {
                card.addEventListener('click', () => {
                    const mode = card.dataset.paymentMode || 'cash';
                    const methodId = card.dataset.methodId || '';
                    setPaymentMethod(mode, methodId, {
                        syncStatus: true
                    });
                });
            });

            if (Array.isArray(initialState.items) && initialState.items.length > 0) {
                initialState.items.forEach((item, index) => {
                    if (item && typeof item === 'object') {
                        cart.push(normalizeOldCartItem(item, index));
                    }
                });
            }

            renderCart();
            if (categoryWrap && catalogItems.length === 0) {
                const firstBadge = categoryWrap.querySelector('[data-category-id]');
                const categoryId = firstBadge?.getAttribute('data-category-id');
                const type = firstBadge?.getAttribute('data-catalog-type');
                if (categoryId) {
                    loadCategoryVariants(categoryId, type, firstBadge);
                }
            }
            const selectedMode = initialState.paymentMode || paymentModeInput?.value || 'cash';
            const defaultCard = document.querySelector(`[data-payment-mode="${selectedMode}"]`) || paymentModeCards[0];
            if (defaultCard) {
                const mode = defaultCard.dataset.paymentMode || 'cash';
                const methodId = initialState.paymentMethod || defaultCard.dataset.methodId || '';
                setPaymentMethod(mode, methodId, {
                    syncStatus: !initialState.hasOldInput
                });
            }

            if (initialState.hasOldInput) {
                if (statusSelect && initialState.status) {
                    statusSelect.value = initialState.status;
                }

                const firstAmount = paymentsList?.querySelector('input[name^="payments"][name$="[amount]"]');
                if (firstAmount instanceof HTMLInputElement && initialState.paymentAmount !== null && initialState
                    .paymentAmount !== '') {
                    firstAmount.value = initialState.paymentAmount;
                    paymentTouched = true;
                }

                const firstMethod = paymentsList?.querySelector('select[name^="payments"][name$="[method_id]"]');
                if (firstMethod instanceof HTMLSelectElement && initialState.paymentMethod) {
                    firstMethod.value = initialState.paymentMethod;
                }

                const firstStatus = paymentsList?.querySelector('select[name^="payments"][name$="[status]"]');
                if (firstStatus instanceof HTMLSelectElement && initialState.paymentStatus) {
                    firstStatus.value = initialState.paymentStatus;
                }

                if (cashInput && initialState.cashReceived !== null && initialState.cashReceived !== '') {
                    cashInput.value = initialState.cashReceived;
                    cashTouched = true;
                }

                calculateTotals();
            }

            if (statusSelect) {
                statusSelect.dispatchEvent(new Event('change'));
            }
        })();
    </script>
@endpush

<div class="card custom-card border-0 shadow-sm h-100">
    <div class="card-body">
        @php
            $enabledFilters ??= [
                'date_range',
                'sale_status',
                'purchase_status',
                'invoice_type',
                'payment_method',
                'customer',
                'vendor',
                'min_total',
                'max_total',
                'search',
            ];
        @endphp
        <form method="GET" action="{{ route($actionRouteName) }}" class="row g-3">
            @if (in_array('date_range', $enabledFilters, true))
                <div class="col-md-4">
                    <label class="form-label" for="reports-daterange">{{ __('Date Range') }}</label>
                    <input type="hidden" id="reports-date-from" name="date_from" value="{{ $filters['date_from'] }}">
                    <input type="hidden" id="reports-date-to" name="date_to" value="{{ $filters['date_to'] }}">
                    <div class="input-group border rounded-2">
                        <div class="input-group-text bg-white border-0 pe-0">
                            <i class="ri-calendar-line lh-1"></i>
                        </div>
                        <input type="text"
                            class="form-control breadcrumb-input border-0 bg-white flatpickr-input js-report-daterange"
                            id="reports-daterange"
                            value="{{ $filters['date_from'] !== '' && $filters['date_to'] !== '' ? $filters['date_from'].' to '.$filters['date_to'] : '' }}"
                            placeholder="{{ __('Search By Date Range') }}" readonly="readonly"
                            data-date-from="{{ $filters['date_from'] }}" data-date-to="{{ $filters['date_to'] }}">
                    </div>
                </div>
            @endif

            @if (in_array('sale_status', $enabledFilters, true))
                <div class="col-md-2">
                    <label class="form-label" for="sale_status">{{ __('Sale Status') }}</label>
                    <select id="sale_status" name="sale_status" class="form-select singl-select-2">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($saleStatuses as $status)
                            <option value="{{ $status->value }}" @selected($filters['sale_status'] === $status->value)>
                                {{ ucfirst(str_replace('_', ' ', $status->value)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if (in_array('purchase_status', $enabledFilters, true))
                <div class="col-md-2">
                    <label class="form-label" for="purchase_status">{{ __('Purchase Status') }}</label>
                    <select id="purchase_status" name="purchase_status" class="form-select singl-select-2">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($purchaseStatuses as $status)
                            <option value="{{ $status->value }}" @selected($filters['purchase_status'] === $status->value)>
                                {{ ucfirst(str_replace('_', ' ', $status->value)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if (in_array('invoice_type', $enabledFilters, true))
                <div class="col-md-2">
                    <label class="form-label" for="invoice_type">{{ __('Invoice Type') }}</label>
                    <select id="invoice_type" name="invoice_type" class="form-select singl-select-2">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($invoiceTypes as $type)
                            <option value="{{ $type->value }}" @selected($filters['invoice_type'] === $type->value)>
                                {{ ucfirst($type->value) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if (in_array('payment_method', $enabledFilters, true))
                <div class="col-md-2">
                    <label class="form-label" for="payment_method">{{ __('Payment Method') }}</label>
                    <select id="payment_method" name="payment_method" class="form-select singl-select-2">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($paymentMethods as $method)
                            <option value="{{ $method->value }}" @selected($filters['payment_method'] === $method->value)>
                                {{ ucfirst($method->value) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if (in_array('customer', $enabledFilters, true))
                <div class="col-md-3">
                    <label class="form-label" for="customer_id">{{ __('Customer') }}</label>
                    <select id="customer_id" name="customer_id" class="form-select singl-select-2">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected((string) $filters['customer_id'] === (string) $customer->id)>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if (in_array('vendor', $enabledFilters, true))
                <div class="col-md-3">
                    <label class="form-label" for="vendor_id">{{ __('Vendor') }}</label>
                    <select id="vendor_id" name="vendor_id" class="form-select singl-select-2">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($vendors as $vendor)
                            <option value="{{ $vendor->id }}" @selected((string) $filters['vendor_id'] === (string) $vendor->id)>
                                {{ $vendor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if (in_array('min_total', $enabledFilters, true))
                <div class="col-md-2">
                    <label class="form-label" for="min_total">{{ __('Min Total') }}</label>
                    <input type="number" step="0.01" id="min_total" name="min_total" class="form-control"
                        value="{{ $filters['min_total'] }}">
                </div>
            @endif

            @if (in_array('max_total', $enabledFilters, true))
                <div class="col-md-2">
                    <label class="form-label" for="max_total">{{ __('Max Total') }}</label>
                    <input type="number" step="0.01" id="max_total" name="max_total" class="form-control"
                        value="{{ $filters['max_total'] }}">
                </div>
            @endif

            @if (in_array('search', $enabledFilters, true))
                <div class="col-md-2">
                    <label class="form-label" for="search">{{ __('Search') }}</label>
                    <input type="text" id="search" name="search" class="form-control"
                        value="{{ $filters['search'] }}" placeholder="{{ __('Invoice, vendor, ref') }}">
                </div>
            @endif
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary w-100">{{ __('Apply') }}</button>
                <a href="{{ route($actionRouteName) }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
            </div>
        </form>
    </div>
</div>

@once
    @push('scripts')
        @vite(['resources/js/reports.js'])
    @endpush
@endonce

@php
    $formMethod = strtoupper($formMethod ?? 'POST');
    $currentJobCard = $jobCard ?? null;

    $serviceLines = old('services');
    if (! is_array($serviceLines)) {
        if ($currentJobCard) {
            $serviceLines = $currentJobCard->services->map(fn ($line): array => [
                'id' => $line->id,
                'service_catalog_id' => $line->service_catalog_id,
                'technician_id' => $line->technician_id,
                'service_name' => $line->service_name,
                'qty' => (string) $line->qty,
                'rate' => (string) $line->rate,
                'status' => $line->status?->value,
                'remarks' => $line->remarks,
            ])->values()->all();
        } else {
            $serviceLines = [];
        }
    }

    $partLines = old('parts');
    if (! is_array($partLines)) {
        if ($currentJobCard) {
            $partLines = $currentJobCard->parts->map(fn ($line): array => [
                'id' => $line->id,
                'product_id' => $line->product_id,
                'qty' => (string) $line->qty,
                'unit_price' => (string) $line->unit_price,
            ])->values()->all();
        } else {
            $partLines = [];
        }
    }
@endphp

<form method="POST" action="{{ $formAction }}">
    @csrf
    @if ($formMethod !== 'POST')
        @method($formMethod)
    @endif

    <div class="row">
        <div class="col-md-3 mb-3">
            <label class="form-label" for="job_no">{{ __('Job No') }}</label>
            <input type="text" name="job_no" id="job_no" class="form-control @error('job_no') is-invalid @enderror"
                value="{{ old('job_no', $currentJobCard?->job_no) }}" required>
            @error('job_no')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label" for="job_date">{{ __('Job Date') }}</label>
            <input type="date" name="job_date" id="job_date"
                class="form-control @error('job_date') is-invalid @enderror"
                value="{{ old('job_date', \App\Support\TenantDateTime::format($currentJobCard?->job_date ?? now(), 'Y-m-d', '')) }}"
                required>
            @error('job_date')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label" for="customer_id">{{ __('Customer') }}</label>
            <select name="customer_id" id="customer_id"
                class="form-select singl-select-2 @error('customer_id') is-invalid @enderror" required>
                <option value="">{{ __('Select customer') }}</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}" @selected(old('customer_id', $currentJobCard?->customer_id) === $customer->id)>
                        {{ $customer->name }} ({{ $customer->code }})
                    </option>
                @endforeach
            </select>
            @error('customer_id')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label" for="vehicle_id">{{ __('Vehicle') }}</label>
            <select name="vehicle_id" id="vehicle_id"
                class="form-select singl-select-2 @error('vehicle_id') is-invalid @enderror">
                <option value="">{{ __('No vehicle') }}</option>
                @foreach ($vehicles as $vehicle)
                    <option value="{{ $vehicle->id }}" @selected(old('vehicle_id', $currentJobCard?->vehicle_id) === $vehicle->id)>
                        {{ $vehicle->registration_no }}
                    </option>
                @endforeach
            </select>
            @error('vehicle_id')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-3 mb-3">
            <label class="form-label" for="assigned_employee_id">{{ __('Assigned Employee') }}</label>
            <select name="assigned_employee_id" id="assigned_employee_id"
                class="form-select singl-select-2 @error('assigned_employee_id') is-invalid @enderror">
                <option value="">{{ __('Unassigned') }}</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" @selected(old('assigned_employee_id', $currentJobCard?->assigned_employee_id) === $employee->id)>
                        {{ $employee->name }}
                    </option>
                @endforeach
            </select>
            @error('assigned_employee_id')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label" for="status">{{ __('Status') }}</label>
            <select name="status" id="status" class="form-select singl-select-2 @error('status') is-invalid @enderror"
                required>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(old('status', $currentJobCard?->status?->value ?? 'new') === $status->value)>
                        {{ ucfirst(str_replace('_', ' ', $status->value)) }}
                    </option>
                @endforeach
            </select>
            @error('status')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label" for="meter_reading">{{ __('Meter Reading') }}</label>
            <input type="number" step="0.001" min="0" name="meter_reading" id="meter_reading"
                class="form-control @error('meter_reading') is-invalid @enderror"
                value="{{ old('meter_reading', $currentJobCard?->meter_reading) }}">
            @error('meter_reading')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label" for="next_reading">{{ __('Next Reading') }}</label>
            <input type="number" step="0.001" min="0" name="next_reading" id="next_reading"
                class="form-control @error('next_reading') is-invalid @enderror"
                value="{{ old('next_reading', $currentJobCard?->next_reading) }}">
            @error('next_reading')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-md-2 mb-3">
            <label class="form-label" for="total_visits">{{ __('Visits') }}</label>
            <input type="number" min="0" name="total_visits" id="total_visits"
                class="form-control @error('total_visits') is-invalid @enderror"
                value="{{ old('total_visits', $currentJobCard?->total_visits ?? 0) }}">
            @error('total_visits')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>
        <div class="col-md-5 mb-3">
            <label class="form-label" for="in_time">{{ __('In Time') }}</label>
            <input type="datetime-local" name="in_time" id="in_time"
                class="form-control @error('in_time') is-invalid @enderror"
                value="{{ old('in_time', \App\Support\TenantDateTime::format($currentJobCard?->in_time, 'Y-m-d\\TH:i', '')) }}">
            @error('in_time')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>
        <div class="col-md-5 mb-3">
            <label class="form-label" for="out_time">{{ __('Out Time') }}</label>
            <input type="datetime-local" name="out_time" id="out_time"
                class="form-control @error('out_time') is-invalid @enderror"
                value="{{ old('out_time', \App\Support\TenantDateTime::format($currentJobCard?->out_time, 'Y-m-d\\TH:i', '')) }}">
            @error('out_time')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>
        <div class="col-md-12 mb-3">
            <label class="form-label" for="remarks">{{ __('Remarks') }}</label>
            <textarea name="remarks" id="remarks" rows="2" class="form-control @error('remarks') is-invalid @enderror">{{ old('remarks', $currentJobCard?->remarks) }}</textarea>
            @error('remarks')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-12 mb-3">
            <div class="card border">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="mb-0">{{ __('Job Services') }}</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="add-job-service-line">
                        {{ __('+ Add Service') }}
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="min-width: 220px;">{{ __('Service Name') }}</th>
                                    <th style="min-width: 220px;">{{ __('Catalog') }}</th>
                                    <th style="min-width: 180px;">{{ __('Technician') }}</th>
                                    <th style="min-width: 100px;">{{ __('Qty') }}</th>
                                    <th style="min-width: 120px;">{{ __('Rate') }}</th>
                                    <th style="min-width: 150px;">{{ __('Status') }}</th>
                                    <th style="min-width: 160px;">{{ __('Remarks') }}</th>
                                    <th style="min-width: 130px;">{{ __('Line Total') }}</th>
                                    <th style="width: 70px;"></th>
                                </tr>
                            </thead>
                            <tbody id="job-service-lines-body" data-next-index="{{ count($serviceLines) }}">
                                @foreach ($serviceLines as $index => $line)
                                    <tr class="job-service-line-row" data-index="{{ $index }}">
                                        <td>
                                            <input type="hidden" name="services[{{ $index }}][id]"
                                                value="{{ $line['id'] ?? '' }}">
                                            <input type="text" name="services[{{ $index }}][service_name]"
                                                class="form-control" value="{{ $line['service_name'] ?? '' }}" required>
                                            @error("services.$index.service_name")
                                                <span class="text-danger small d-block">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td>
                                            <select name="services[{{ $index }}][service_catalog_id]"
                                                class="form-select singl-select-2">
                                                <option value="">{{ __('None') }}</option>
                                                @foreach ($serviceCatalogs as $catalog)
                                                    <option value="{{ $catalog->id }}" @selected(($line['service_catalog_id'] ?? '') === $catalog->id)>
                                                        {{ $catalog->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error("services.$index.service_catalog_id")
                                                <span class="text-danger small d-block">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td>
                                            <select name="services[{{ $index }}][technician_id]"
                                                class="form-select singl-select-2">
                                                <option value="">{{ __('None') }}</option>
                                                @foreach ($employees as $employee)
                                                    <option value="{{ $employee->id }}" @selected(($line['technician_id'] ?? '') === $employee->id)>
                                                        {{ $employee->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error("services.$index.technician_id")
                                                <span class="text-danger small d-block">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td>
                                            <input type="number" step="0.001" min="0.001"
                                                name="services[{{ $index }}][qty]"
                                                class="form-control job-service-line-qty" value="{{ $line['qty'] ?? '1' }}"
                                                required>
                                            @error("services.$index.qty")
                                                <span class="text-danger small d-block">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0"
                                                name="services[{{ $index }}][rate]"
                                                class="form-control job-service-line-rate"
                                                value="{{ $line['rate'] ?? '0' }}" required>
                                            @error("services.$index.rate")
                                                <span class="text-danger small d-block">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td>
                                            <select name="services[{{ $index }}][status]" class="form-select" required>
                                                @foreach ($serviceStatuses as $status)
                                                    <option value="{{ $status->value }}" @selected(($line['status'] ?? 'pending') === $status->value)>
                                                        {{ ucfirst(str_replace('_', ' ', $status->value)) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error("services.$index.status")
                                                <span class="text-danger small d-block">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td>
                                            <input type="text" name="services[{{ $index }}][remarks]"
                                                class="form-control" value="{{ $line['remarks'] ?? '' }}">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control job-service-line-total" value="0.00"
                                                readonly>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-danger-light job-service-remove-line">
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
            @error('services')
                <span class="text-danger small d-block">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-12 mb-3">
            <div class="card border">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="mb-0">{{ __('Job Parts') }}</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="add-job-part-line">
                        {{ __('+ Add Part') }}
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="min-width: 260px;">{{ __('Product') }}</th>
                                    <th style="min-width: 120px;">{{ __('Qty') }}</th>
                                    <th style="min-width: 140px;">{{ __('Unit Price') }}</th>
                                    <th style="min-width: 140px;">{{ __('Line Total') }}</th>
                                    <th style="width: 70px;"></th>
                                </tr>
                            </thead>
                            <tbody id="job-part-lines-body" data-next-index="{{ count($partLines) }}">
                                @foreach ($partLines as $index => $line)
                                    <tr class="job-part-line-row" data-index="{{ $index }}">
                                        <td>
                                            <input type="hidden" name="parts[{{ $index }}][id]"
                                                value="{{ $line['id'] ?? '' }}">
                                            <select name="parts[{{ $index }}][product_id]"
                                                class="form-select singl-select-2" required>
                                                <option value="">{{ __('Select product') }}</option>
                                                @foreach ($products as $product)
                                                    <option value="{{ $product->id }}" @selected(($line['product_id'] ?? '') === $product->id)>
                                                        {{ $product->name }} {{ $product->sku ? '(' . $product->sku . ')' : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error("parts.$index.product_id")
                                                <span class="text-danger small d-block">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td>
                                            <input type="number" step="0.001" min="0.001"
                                                name="parts[{{ $index }}][qty]"
                                                class="form-control job-part-line-qty" value="{{ $line['qty'] ?? '1' }}"
                                                required>
                                            @error("parts.$index.qty")
                                                <span class="text-danger small d-block">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0"
                                                name="parts[{{ $index }}][unit_price]"
                                                class="form-control job-part-line-price"
                                                value="{{ $line['unit_price'] ?? '0' }}" required>
                                            @error("parts.$index.unit_price")
                                                <span class="text-danger small d-block">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td>
                                            <input type="text" class="form-control job-part-line-total" value="0.00"
                                                readonly>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-danger-light job-part-remove-line">
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
            @error('parts')
                <span class="text-danger small d-block">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
</form>

<template id="job-service-line-template">
    <tr class="job-service-line-row" data-index="__INDEX__">
        <td>
            <input type="hidden" name="services[__INDEX__][id]" value="">
            <input type="text" name="services[__INDEX__][service_name]" class="form-control" required>
        </td>
        <td>
            <select name="services[__INDEX__][service_catalog_id]" class="form-select singl-select-2">
                <option value="">{{ __('None') }}</option>
                @foreach ($serviceCatalogs as $catalog)
                    <option value="{{ $catalog->id }}">{{ $catalog->name }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <select name="services[__INDEX__][technician_id]" class="form-select singl-select-2">
                <option value="">{{ __('None') }}</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                @endforeach
            </select>
        </td>
        <td><input type="number" step="0.001" min="0.001" name="services[__INDEX__][qty]" class="form-control job-service-line-qty" value="1" required></td>
        <td><input type="number" step="0.01" min="0" name="services[__INDEX__][rate]" class="form-control job-service-line-rate" value="0" required></td>
        <td>
            <select name="services[__INDEX__][status]" class="form-select" required>
                @foreach ($serviceStatuses as $status)
                    <option value="{{ $status->value }}" @selected($status->value === 'pending')>
                        {{ ucfirst(str_replace('_', ' ', $status->value)) }}
                    </option>
                @endforeach
            </select>
        </td>
        <td><input type="text" name="services[__INDEX__][remarks]" class="form-control"></td>
        <td><input type="text" class="form-control job-service-line-total" value="0.00" readonly></td>
        <td>
            <button type="button" class="btn btn-sm btn-danger-light job-service-remove-line">
                <i class="ri-delete-bin-line"></i>
            </button>
        </td>
    </tr>
</template>

<template id="job-part-line-template">
    <tr class="job-part-line-row" data-index="__INDEX__">
        <td>
            <input type="hidden" name="parts[__INDEX__][id]" value="">
            <select name="parts[__INDEX__][product_id]" class="form-select singl-select-2" required>
                <option value="">{{ __('Select product') }}</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }} {{ $product->sku ? '(' . $product->sku . ')' : '' }}</option>
                @endforeach
            </select>
        </td>
        <td><input type="number" step="0.001" min="0.001" name="parts[__INDEX__][qty]" class="form-control job-part-line-qty" value="1" required></td>
        <td><input type="number" step="0.01" min="0" name="parts[__INDEX__][unit_price]" class="form-control job-part-line-price" value="0" required></td>
        <td><input type="text" class="form-control job-part-line-total" value="0.00" readonly></td>
        <td>
            <button type="button" class="btn btn-sm btn-danger-light job-part-remove-line">
                <i class="ri-delete-bin-line"></i>
            </button>
        </td>
    </tr>
</template>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const serviceBody = document.getElementById('job-service-lines-body');
            const partBody = document.getElementById('job-part-lines-body');

            if (!serviceBody || !partBody) {
                return;
            }

            const parseNumber = (value) => {
                const parsed = parseFloat(value);
                return Number.isFinite(parsed) ? parsed : 0;
            };

            const initSelect2 = (element) => {
                if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) {
                    return;
                }

                const $element = window.jQuery(element);
                if ($element.hasClass('select2-hidden-accessible')) {
                    return;
                }

                $element.select2({ width: '100%' });
            };

            const recalculateServiceRow = (row) => {
                const qty = parseNumber(row.querySelector('.job-service-line-qty')?.value);
                const rate = parseNumber(row.querySelector('.job-service-line-rate')?.value);
                const totalInput = row.querySelector('.job-service-line-total');

                if (totalInput) {
                    totalInput.value = (qty * rate).toFixed(2);
                }
            };

            const recalculatePartRow = (row) => {
                const qty = parseNumber(row.querySelector('.job-part-line-qty')?.value);
                const unitPrice = parseNumber(row.querySelector('.job-part-line-price')?.value);
                const totalInput = row.querySelector('.job-part-line-total');

                if (totalInput) {
                    totalInput.value = (qty * unitPrice).toFixed(2);
                }
            };

            const initializeServiceRow = (row) => {
                row.querySelectorAll('.singl-select-2').forEach((select) => initSelect2(select));
                recalculateServiceRow(row);
            };

            const initializePartRow = (row) => {
                row.querySelectorAll('.singl-select-2').forEach((select) => initSelect2(select));
                recalculatePartRow(row);
            };

            const addServiceRow = () => {
                const template = document.getElementById('job-service-line-template');
                const index = parseInt(serviceBody.dataset.nextIndex ?? '0', 10);
                const html = template.innerHTML.replaceAll('__INDEX__', String(index));
                serviceBody.insertAdjacentHTML('beforeend', html);
                serviceBody.dataset.nextIndex = String(index + 1);

                const row = serviceBody.querySelector('.job-service-line-row:last-child');
                if (row) {
                    initializeServiceRow(row);
                }
            };

            const addPartRow = () => {
                const template = document.getElementById('job-part-line-template');
                const index = parseInt(partBody.dataset.nextIndex ?? '0', 10);
                const html = template.innerHTML.replaceAll('__INDEX__', String(index));
                partBody.insertAdjacentHTML('beforeend', html);
                partBody.dataset.nextIndex = String(index + 1);

                const row = partBody.querySelector('.job-part-line-row:last-child');
                if (row) {
                    initializePartRow(row);
                }
            };

            document.getElementById('add-job-service-line')?.addEventListener('click', addServiceRow);
            document.getElementById('add-job-part-line')?.addEventListener('click', addPartRow);

            serviceBody.addEventListener('input', (event) => {
                const target = event.target;
                if (!(target instanceof HTMLElement)) {
                    return;
                }

                if (target.classList.contains('job-service-line-qty') || target.classList.contains('job-service-line-rate')) {
                    const row = target.closest('.job-service-line-row');
                    if (row) {
                        recalculateServiceRow(row);
                    }
                }
            });

            partBody.addEventListener('input', (event) => {
                const target = event.target;
                if (!(target instanceof HTMLElement)) {
                    return;
                }

                if (target.classList.contains('job-part-line-qty') || target.classList.contains('job-part-line-price')) {
                    const row = target.closest('.job-part-line-row');
                    if (row) {
                        recalculatePartRow(row);
                    }
                }
            });

            serviceBody.addEventListener('click', (event) => {
                const target = event.target;
                if (!(target instanceof HTMLElement)) {
                    return;
                }

                const removeButton = target.closest('.job-service-remove-line');
                if (!removeButton) {
                    return;
                }

                removeButton.closest('.job-service-line-row')?.remove();
            });

            partBody.addEventListener('click', (event) => {
                const target = event.target;
                if (!(target instanceof HTMLElement)) {
                    return;
                }

                const removeButton = target.closest('.job-part-remove-line');
                if (!removeButton) {
                    return;
                }

                removeButton.closest('.job-part-line-row')?.remove();
            });

            serviceBody.querySelectorAll('.job-service-line-row').forEach((row) => initializeServiceRow(row));
            partBody.querySelectorAll('.job-part-line-row').forEach((row) => initializePartRow(row));
        });
    </script>
@endpush

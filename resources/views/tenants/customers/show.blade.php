@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('People')],
            ['label' => __('Customers'), 'url' => route('tenant.customers.index')],
            ['label' => __('Details')],
        ];

        $statusClass = match ($customer->status->value) {
            'active' => 'bg-success-transparent',
            'inactive' => 'bg-secondary-transparent',
            default => 'bg-danger-transparent',
        };
        $customerRows = [
            ['label' => __('Code'), 'value' => $customer->code],
            ['label' => __('Phone'), 'value' => $customer->phone ?: null],
            ['label' => __('Email'), 'value' => $customer->email ?: null],
            ['label' => __('Branch'), 'value' => $customer->branch?->name],
            ['label' => __('CNIC'), 'value' => $customer->cnic ?: null],
            ['label' => __('NTN'), 'value' => $customer->ntn ?: null],
            ['label' => __('City'), 'value' => $customer->city ?: null],
            ['label' => __('Credit Limit'), 'value' => number_format((float) $customer->credit_limit, 2)],
            ['label' => __('Opening Balance'), 'value' => number_format((float) $customer->opening_balance, 2)],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Customer Details') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.customers.edit', $customer) }}" class="btn btn-secondary">{{ __('Edit') }}</a>
            <a href="{{ route('tenant.customers.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
                <div>
                    <div class="text-muted small">{{ __('Customer') }}</div>
                    <h5 class="mb-1">{{ $customer->name }}</h5>
                </div>
                <span class="badge {{ $statusClass }}">{{ ucfirst($customer->status->value) }}</span>
            </div>

            <div class="row g-3">
                @foreach ($customerRows as $customerRow)
                    @if ($customerRow['value'] !== null && $customerRow['value'] !== '')
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">{{ $customerRow['label'] }}</div>
                                <div class="fw-semibold">{{ $customerRow['value'] }}</div>
                            </div>
                        </div>
                    @endif
                @endforeach

                @if ($customer->address)
                    <div class="col-12">
                        <div class="border rounded p-3">
                            <div class="text-muted small">{{ __('Address') }}</div>
                            <div class="fw-semibold">{{ $customer->address }}</div>
                        </div>
                    </div>
                @endif

                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Vehicles') }}</div>
                        <div class="fw-semibold">{{ $customer->vehicles->count() }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Recent Sales') }}</div>
                        <div class="fw-semibold">{{ $recentSales->count() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h6 class="mb-0">{{ __('Vehicles') }}</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Registration') }}</th>
                            <th>{{ __('Model') }}</th>
                            <th>{{ __('Year') }}</th>
                            <th>{{ __('Meter Reading') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($customer->vehicles as $vehicle)
                            <tr>
                                <td>{{ $vehicle->registration_no }}</td>
                                <td>{{ $vehicle->model ?: '-' }}</td>
                                <td>{{ $vehicle->year ?: '-' }}</td>
                                <td>{{ number_format((float) $vehicle->meter_reading, 3) }}</td>
                                <td class="text-end">
                                    <a href="{{ route('tenant.customer-vehicles.show', $vehicle) }}"
                                        class="btn btn-sm btn-icon btn-primary-light btn-wave waves-effect waves-light"
                                        title="{{ __('View') }}">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">{{ __('No vehicles found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h6 class="mb-0">{{ __('Recent Sales') }}</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Invoice No') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="text-end">{{ __('Grand Total') }}</th>
                            <th class="text-end">{{ __('Balance Due') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentSales as $sale)
                            <tr>
                                <td>{{ $sale->invoice_no }}</td>
                                <td>{{ $sale->invoice_date?->format('Y-m-d') ?? '-' }}</td>
                                <td>{{ ucfirst($sale->status->value) }}</td>
                                <td class="text-end">{{ number_format((float) $sale->grand_total, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $sale->balance_due, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">{{ __('No sales found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

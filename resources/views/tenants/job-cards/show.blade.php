@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Workshop')],
            ['label' => __('Job Cards'), 'url' => route('tenant.job-cards.index')],
            ['label' => __('Details')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Job Card Details') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.job-cards.edit', $jobCard) }}" class="btn btn-outline-primary">{{ __('Edit') }}</a>
            <a href="{{ route('tenant.job-cards.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
                <div>
                    <div class="text-muted small">{{ __('Job No') }}</div>
                    <h5 class="mb-1">{{ $jobCard->job_no }}</h5>
                    <div class="text-muted small">{{ __('Date') }}: {{ $jobCard->job_date?->format('Y-m-d') ?? '-' }}</div>
                </div>
                <div class="text-end">
                    <div class="text-muted small">{{ __('Status') }}</div>
                    <div class="fw-semibold">{{ ucfirst(str_replace('_', ' ', $jobCard->status->value)) }}</div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Customer') }}</div>
                        <div class="fw-semibold">{{ $jobCard->customer?->name ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Vehicle') }}</div>
                        <div class="fw-semibold">{{ $jobCard->vehicle?->registration_no ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Assigned Employee') }}</div>
                        <div class="fw-semibold">{{ $jobCard->assignedEmployee?->name ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Total Visits') }}</div>
                        <div class="fw-semibold">{{ $jobCard->total_visits ?? 0 }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Meter Reading') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $jobCard->meter_reading, 3) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Next Reading') }}</div>
                        <div class="fw-semibold">{{ number_format((float) $jobCard->next_reading, 3) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('In Time') }}</div>
                        <div class="fw-semibold">{{ $jobCard->in_time?->format('Y-m-d H:i') ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Out Time') }}</div>
                        <div class="fw-semibold">{{ $jobCard->out_time?->format('Y-m-d H:i') ?? '-' }}</div>
                    </div>
                </div>
            </div>

            @if ($jobCard->remarks)
                <div class="border rounded p-3 mt-3">
                    <div class="text-muted small">{{ __('Remarks') }}</div>
                    <div class="fw-semibold">{{ $jobCard->remarks }}</div>
                </div>
            @endif
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h6 class="mb-0">{{ __('Service Lines') }}</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Service') }}</th>
                            <th>{{ __('Technician') }}</th>
                            <th class="text-end">{{ __('Qty') }}</th>
                            <th class="text-end">{{ __('Rate') }}</th>
                            <th class="text-end">{{ __('Line Total') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($jobCard->services as $serviceLine)
                            <tr>
                                <td>{{ $serviceLine->service_name }}</td>
                                <td>{{ $serviceLine->technician?->name ?? '-' }}</td>
                                <td class="text-end">{{ number_format((float) $serviceLine->qty, 3) }}</td>
                                <td class="text-end">{{ number_format((float) $serviceLine->rate, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $serviceLine->line_total, 2) }}</td>
                                <td class="text-end">
                                    <a href="{{ route('tenant.job-card-services.show', $serviceLine) }}"
                                        class="btn btn-sm btn-icon btn-primary-light btn-wave waves-effect waves-light">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">{{ __('No service lines found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h6 class="mb-0">{{ __('Part Lines') }}</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Product') }}</th>
                            <th class="text-end">{{ __('Qty') }}</th>
                            <th class="text-end">{{ __('Unit Price') }}</th>
                            <th class="text-end">{{ __('Line Total') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($jobCard->parts as $partLine)
                            <tr>
                                <td>{{ $partLine->product?->name ?? '-' }}</td>
                                <td class="text-end">{{ number_format((float) $partLine->qty, 3) }}</td>
                                <td class="text-end">{{ number_format((float) $partLine->unit_price, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $partLine->line_total, 2) }}</td>
                                <td class="text-end">
                                    <a href="{{ route('tenant.job-card-parts.show', $partLine) }}"
                                        class="btn btn-sm btn-icon btn-primary-light btn-wave waves-effect waves-light">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">{{ __('No part lines found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

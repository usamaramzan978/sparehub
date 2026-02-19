@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [['label' => __('Operations')], ['label' => __('End Of Day')]];
        $currency = static fn(float $value): string => number_format($value, 2);
    @endphp

    <x-breadcrumb title="{{ __('End Of Day') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <form method="GET" action="{{ route('tenant.end-of-day') }}" class="d-flex align-items-end gap-2">
                <div>
                    <label for="eod-date" class="form-label mb-1">{{ __('Date') }}</label>
                    <input id="eod-date" type="date" name="date" class="form-control" value="{{ $selectedDate }}">
                </div>
                <button type="submit" class="btn btn-primary">{{ __('Load') }}</button>
            </form>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="row g-3">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('Sales Today') }}</div>
                    <h4>{{ $currency($summary['sales_total']) }}</h4>
                    <div class="small text-muted">{{ $summary['sales_count'] }} {{ __('invoices') }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('Purchases Today') }}</div>
                    <h4>{{ $currency($summary['purchases_total']) }}</h4>
                    <div class="small text-muted">{{ $summary['purchases_count'] }} {{ __('orders') }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('Cash In / Out') }}</div>
                    <h4>{{ $currency($summary['cash_net']) }}</h4>
                    <div class="small text-muted">{{ __('In') }} {{ $currency($summary['cash_in']) }} |
                        {{ __('Out') }} {{ $currency($summary['cash_out']) }}</div>
                    <div class="small text-muted">{{ __('Out includes expenses') }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('Expenses Today') }}</div>
                    <h4>{{ $currency($summary['expenses_total']) }}</h4>
                    <div class="small text-muted">{{ $summary['expenses_count'] }} {{ __('entries') }}</div>
                    <a href="{{ route('tenant.expenses.index', ['date_from' => $selectedDate, 'date_to' => $selectedDate]) }}"
                        class="btn btn-outline-primary btn-sm mt-2">{{ __('Open Expenses') }}</a>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('Open Job Cards') }}</div>
                    <h4>{{ $summary['open_job_cards'] }}</h4>
                    <div class="small text-muted">{{ __('Need follow-up') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-12 col-xl-6">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-header">
                    <h6 class="mb-0">{{ __('Employee Attendance Snapshot') }}</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between py-1">
                        <span>{{ __('Checked In') }}</span><strong>{{ $summary['checked_in_count'] }}</strong></div>
                    <div class="d-flex justify-content-between py-1">
                        <span>{{ __('Checked Out') }}</span><strong>{{ $summary['checked_out_count'] }}</strong></div>
                    <div class="d-flex justify-content-between py-1"><span>{{ __('Missing Check Out') }}</span><strong
                            class="text-danger">{{ $summary['missing_checkout_count'] }}</strong></div>
                    <a href="{{ route('tenant.employee-attendances.index', ['attendance_date' => $selectedDate]) }}"
                        class="btn btn-outline-primary btn-sm mt-3">{{ __('Open Attendance') }}</a>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-header">
                    <h6 class="mb-0">{{ __('Payroll Snapshot (Current Month)') }}</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between py-1">
                        <span>{{ __('Total Payroll') }}</span><strong>{{ $currency($summary['payroll_total']) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-1"><span>{{ __('Paid') }}</span><strong
                            class="text-success">{{ $currency($summary['payroll_paid_total']) }}</strong></div>
                    <div class="d-flex justify-content-between py-1"><span>{{ __('Unpaid') }}</span><strong
                            class="text-warning">{{ $currency($summary['payroll_unpaid_total']) }}</strong></div>
                    <a href="{{ route('tenant.employee-salaries.index', ['salary_month' => \Carbon\Carbon::parse($selectedDate)->format('Y-m')]) }}"
                        class="btn btn-outline-primary btn-sm mt-3">{{ __('Open Salaries') }}</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-12 col-xl-6">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-header">
                    <h6 class="mb-0">{{ __('Recent Sales') }}</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Invoice') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th class="text-end">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentSales as $sale)
                                    <tr>
                                        <td>{{ $sale->invoice_no }}</td>
                                        <td>{{ $sale->invoice_date?->format('Y-m-d') }}</td>
                                        <td class="text-end">{{ $currency((float) $sale->grand_total) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">{{ __('No sales found.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-header">
                    <h6 class="mb-0">{{ __('Recent Purchases') }}</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Purchase') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th class="text-end">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentPurchases as $purchase)
                                    <tr>
                                        <td>{{ $purchase->purchase_no }}</td>
                                        <td>{{ $purchase->purchase_date?->format('Y-m-d') }}</td>
                                        <td class="text-end">{{ $currency((float) $purchase->grand_total) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">{{ __('No purchases found.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

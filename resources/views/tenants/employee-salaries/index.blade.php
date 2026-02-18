@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('People')],
            ['label' => __('Employee Salaries')],
        ];
        $currency = static fn (float $value): string => number_format($value, 2);
    @endphp

    <x-breadcrumb title="{{ __('Employee Salaries') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <form method="GET" action="{{ route('tenant.employee-salaries.index') }}" class="d-flex align-items-end gap-2">
                <div>
                    <label for="salary-month" class="form-label mb-1">{{ __('Month') }}</label>
                    <input id="salary-month" type="month" name="salary_month" class="form-control"
                        value="{{ $salaryMonth }}">
                </div>
                <button type="submit" class="btn btn-primary">{{ __('Apply') }}</button>
            </form>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">{{ __('Employees') }}</div><h4 class="mb-0">{{ $summary['employees_count'] }}</h4></div></div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">{{ __('Configured') }}</div><h4 class="mb-0">{{ $summary['configured_count'] }}</h4></div></div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">{{ __('Paid Total') }}</div><h4 class="mb-0">{{ $currency($summary['paid_total']) }}</h4></div></div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">{{ __('Unpaid Total') }}</div><h4 class="mb-0">{{ $currency($summary['unpaid_total']) }}</h4></div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Employee') }}</th>
                            <th>{{ __('Basic') }}</th>
                            <th>{{ __('Bonus') }}</th>
                            <th>{{ __('Deduction') }}</th>
                            <th>{{ __('Net') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employees as $employee)
                            @php
                                $record = $records->get($employee->id);
                                $basic = (float) ($record?->basic_salary ?? 0);
                                $bonus = (float) ($record?->bonus ?? 0);
                                $deduction = (float) ($record?->deduction ?? 0);
                                $net = max(($basic + $bonus) - $deduction, 0);
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $employee->name }}</div>
                                    <small class="text-muted">{{ $employee->email }}</small>
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" class="form-control form-control-sm"
                                        name="basic_salary" form="salary-form-{{ $employee->id }}"
                                        value="{{ number_format($basic, 2, '.', '') }}" placeholder="{{ __('Basic') }}" required>
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" class="form-control form-control-sm"
                                        name="bonus" form="salary-form-{{ $employee->id }}"
                                        value="{{ number_format($bonus, 2, '.', '') }}" placeholder="{{ __('Bonus') }}">
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" class="form-control form-control-sm"
                                        name="deduction" form="salary-form-{{ $employee->id }}"
                                        value="{{ number_format($deduction, 2, '.', '') }}"
                                        placeholder="{{ __('Deduction') }}">
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm" value="{{ $currency($net) }}"
                                        readonly>
                                </td>
                                <td>
                                    @if ($record?->paid_at)
                                        <span class="badge bg-success-transparent">{{ __('Paid') }}</span>
                                        <div><small class="text-muted">{{ $record->paid_at->format('Y-m-d') }}</small></div>
                                    @else
                                        <span class="badge bg-warning-transparent">{{ __('Pending') }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <form id="salary-form-{{ $employee->id }}" method="POST"
                                        action="{{ route('tenant.employee-salaries.store') }}">
                                        @csrf
                                        <input type="hidden" name="user_id" value="{{ $employee->id }}">
                                        <input type="hidden" name="salary_month" value="{{ $salaryMonth }}">
                                        <div class="btn-list justify-content-end">
                                            <button type="submit" name="action" value="save"
                                                class="btn btn-sm btn-primary">{{ __('Save') }}</button>
                                            <button type="submit" name="action" value="mark_paid"
                                                class="btn btn-sm btn-success" @disabled($record?->paid_at !== null)>
                                                {{ __('Mark Paid') }}
                                            </button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">{{ __('No employees found for this branch.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

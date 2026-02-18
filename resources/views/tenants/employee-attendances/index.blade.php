@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [['label' => __('People')], ['label' => __('Employee Attendance')]];
    @endphp

    <x-breadcrumb title="{{ __('Employee Attendance') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <form method="GET" action="{{ route('tenant.employee-attendances.index') }}"
                class="d-flex align-items-end gap-2">
                <div>
                    <label for="attendance-date" class="form-label mb-1">{{ __('Date') }}</label>
                    <input id="attendance-date" type="date" name="attendance_date" class="form-control"
                        value="{{ $attendanceDate }}">
                </div>
                <button type="submit" class="btn btn-primary">{{ __('Apply') }}</button>
            </form>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('Employees') }}</div>
                    <h4 class="mb-0">{{ $summary['employees_count'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('Checked In') }}</div>
                    <h4 class="mb-0">{{ $summary['checked_in_count'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('Checked Out') }}</div>
                    <h4 class="mb-0">{{ $summary['checked_out_count'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('Missing Check Out') }}</div>
                    <h4 class="mb-0">{{ $summary['missing_checkout_count'] }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Employee') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Check In') }}</th>
                            <th>{{ __('Check Out') }}</th>
                            <th>{{ __('Work Time') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employees as $employee)
                            @php
                                $record = $records->get($employee->id);
                                $isCheckedOut = $record?->check_out_at !== null;
                                $isCheckedIn = $record?->check_in_at !== null;
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $employee->name }}</div>
                                    <small class="text-muted">{{ $employee->email }}</small>
                                </td>
                                <td>
                                    @if ($isCheckedOut)
                                        <span class="badge bg-success-transparent">{{ __('Completed') }}</span>
                                    @elseif ($isCheckedIn)
                                        <span class="badge bg-warning-transparent">{{ __('Checked In') }}</span>
                                    @else
                                        <span class="badge bg-secondary-transparent">{{ __('Absent / Not Marked') }}</span>
                                    @endif
                                </td>
                                <td>{{ $record?->check_in_at?->format('H:i') ?? '-' }}</td>
                                <td>{{ $record?->check_out_at?->format('H:i') ?? '-' }}</td>
                                <td>
                                    @if ($record?->total_minutes)
                                        {{ intdiv((int) $record->total_minutes, 60) }}h
                                        {{ (int) $record->total_minutes % 60 }}m
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-list justify-content-end">
                                        <form method="POST" action="{{ route('tenant.employee-attendances.store') }}"
                                            class="d-inline">
                                            @csrf
                                            <input type="hidden" name="user_id" value="{{ $employee->id }}">
                                            <input type="hidden" name="attendance_date" value="{{ $attendanceDate }}">
                                            <input type="hidden" name="action" value="check_in">
                                            <button type="submit" class="btn btn-sm btn-primary"
                                                @disabled($isCheckedIn)>
                                                {{ __('Check In') }}
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('tenant.employee-attendances.store') }}"
                                            class="d-inline">
                                            @csrf
                                            <input type="hidden" name="user_id" value="{{ $employee->id }}">
                                            <input type="hidden" name="attendance_date" value="{{ $attendanceDate }}">
                                            <input type="hidden" name="action" value="check_out">
                                            <button type="submit" class="btn btn-sm btn-success"
                                                @disabled(!$isCheckedIn || $isCheckedOut)>
                                                {{ __('Check Out') }}
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('tenant.employee-attendances.store') }}"
                                            class="d-inline">
                                            @csrf
                                            <input type="hidden" name="user_id" value="{{ $employee->id }}">
                                            <input type="hidden" name="attendance_date" value="{{ $attendanceDate }}">
                                            <input type="hidden" name="action" value="mark_absent">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                {{ __('Mark Absent') }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    {{ __('No employees found for this branch.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

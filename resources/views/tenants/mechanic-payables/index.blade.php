@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [['label' => __('Employees')], ['label' => __('Mechanic Payables')]];
        $currency = static fn(float $value): string => number_format($value, 2);
    @endphp

    <x-breadcrumb title="{{ __('Mechanic Payables') }}" :items="$breadcrumbs" />

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('Payable Entries') }}</div>
                    <h4 class="mb-0">{{ (int) $summary['lines_count'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('Total Mechanic Payable') }}</div>
                    <h4 class="mb-0">{{ $currency((float) $summary['total_payable']) }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card custom-card border-0 shadow-sm h-100 mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('tenant.mechanic-payables.index') }}" class="row g-2">
                <div class="col-md-3">
                    <label class="form-label" for="search">{{ __('Search') }}</label>
                    <input type="text" name="search" id="search" class="form-control" value="{{ request('search') }}"
                        placeholder="{{ __('Invoice / service / mechanic') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="mechanic_id">{{ __('Mechanic') }}</label>
                    <select name="mechanic_id" id="mechanic_id" class="form-select singl-select-2">
                        <option value="">{{ __('All Mechanics') }}</option>
                        @foreach ($mechanics as $mechanic)
                            <option value="{{ $mechanic->id }}" @selected(request('mechanic_id') === $mechanic->id)>
                                {{ $mechanic->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="date_from">{{ __('Date From') }}</label>
                    <input type="date" name="date_from" id="date_from" class="form-control"
                        value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="date_to">{{ __('Date To') }}</label>
                    <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100">{{ __('Filter') }}</button>
                    <a href="{{ route('tenant.mechanic-payables.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-4">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-header">
                    <h6 class="mb-0">{{ __('By Mechanic') }}</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Mechanic') }}</th>
                                    <th class="text-end">{{ __('Entries') }}</th>
                                    <th class="text-end">{{ __('Payable') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($byMechanic as $row)
                                    <tr>
                                        <td>{{ $row['mechanic_name'] }}</td>
                                        <td class="text-end">{{ $row['lines_count'] }}</td>
                                        <td class="text-end">{{ $currency($row['total_payable']) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">{{ __('No payable records found.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-8">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-header">
                    <h6 class="mb-0">{{ __('Payable Lines') }}</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Invoice') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Mechanic') }}</th>
                                    <th>{{ __('Service') }}</th>
                                    <th class="text-end">{{ __('Customer Charge') }}</th>
                                    <th class="text-end">{{ __('Mechanic Payable') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $item)
                                    <tr>
                                        <td>{{ $item->sale?->invoice_no ?? '-' }}</td>
                                        <td>@tenantDate($item->sale?->invoice_date, 'Y-m-d')</td>
                                        <td>{{ $item->mechanic?->name ?? '-' }}</td>
                                        <td>{{ $item->description ?: ($item->serviceCatalog?->name ?? '-') }}</td>
                                        <td class="text-end">{{ $currency((float) $item->line_total) }}</td>
                                        <td class="text-end">{{ $currency((float) $item->mechanic_charge) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">{{ __('No payable lines found.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $items->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [['label' => __('Finance')], ['label' => __('Expenses')]];
        $currency = static fn(float $value): string => number_format($value, 2);
    @endphp

    @include('tenants.expenses.partials.create', ['methods' => $methods])

    <x-breadcrumb title="{{ __('Expenses') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#expenseCreateModal">
                {{ __('Add Expense') }}
            </button>
        </x-slot:actions>
    </x-breadcrumb>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('Expense Entries') }}</div>
                    <h4 class="mb-0">{{ (int) $summary['count'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('Expense Total') }}</div>
                    <h4 class="mb-0">{{ $currency((float) $summary['total']) }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-body">
            <form method="GET" action="{{ route('tenant.expenses.index') }}" class="row g-2 mb-3">
                <div class="col-md-4">
                    <label class="form-label" for="search">{{ __('Search') }}</label>
                    <input type="text" name="search" id="search" class="form-control" value="{{ request('search') }}"
                        placeholder="{{ __('Search title / category / reference') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="date_from">{{ __('Date From') }}</label>
                    <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="date_to">{{ __('Date To') }}</label>
                    <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100">{{ __('Filter') }}</button>
                    <a href="{{ route('tenant.expenses.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Title') }}</th>
                            <th>{{ __('Category') }}</th>
                            <th>{{ __('Payment Method') }}</th>
                            <th class="text-end">{{ __('Amount') }}</th>
                            <th>{{ __('Reference') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $expense)
                            <tr>
                                <td>@tenantDate($expense->expense_date, 'Y-m-d', '')</td>
                                <td>{{ $expense->title }}</td>
                                <td>{{ $expense->category ?: '-' }}</td>
                                <td>{{ ucfirst($expense->payment_method->value) }}</td>
                                <td class="text-end">{{ $currency((float) $expense->amount) }}</td>
                                <td>{{ $expense->reference_no ?: '-' }}</td>
                                <td class="text-end">
                                    <div class="btn-list">
                                        <span class="d-inline-block" data-bs-toggle="tooltip" title="{{ __('Edit') }}">
                                            <button type="button"
                                                class="btn btn-sm btn-icon btn-secondary-light btn-wave waves-effect waves-light"
                                                data-bs-toggle="modal" data-bs-target="#expenseEditModal-{{ $expense->id }}">
                                                <i class="ri-edit-line"></i>
                                            </button>
                                        </span>
                                        <span class="d-inline-block" data-bs-toggle="tooltip" title="{{ __('Delete') }}">
                                            <button type="button"
                                                class="btn btn-sm btn-icon btn-danger-light btn-wave waves-effect waves-light js-delete-modal"
                                                data-action="{{ route('tenant.expenses.destroy', $expense) }}"
                                                data-name="{{ $expense->title }}" data-title="{{ __('Delete Expense') }}"
                                                data-message="{{ __('Are you sure you want to delete this expense?') }}"
                                                data-bs-toggle="modal" data-bs-target="#expenseDeleteModal">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            @include('tenants.expenses.partials.edit', [
                                'expense' => $expense,
                                'methods' => $methods,
                            ])
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">{{ __('No expenses found.') }}</td>
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

    <x-delete-modal id="expenseDeleteModal" />
@endsection

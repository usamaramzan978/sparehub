@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('People')],
            ['label' => __('Users'), 'url' => route('tenant.users.index')],
            ['label' => __('Details')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('User Details') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.users.edit', $user) }}" class="btn btn-secondary">{{ __('Edit') }}</a>
            <button type="button" class="btn btn-danger js-delete-modal"
                data-action="{{ route('tenant.users.destroy', $user) }}" data-name="{{ $user->name }}"
                data-title="{{ __('Delete User') }}" data-message="{{ __('Are you sure you want to delete this user?') }}"
                data-bs-toggle="modal" data-bs-target="#userDeleteModal">
                {{ __('Delete') }}
            </button>
            <a href="{{ route('tenant.users.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card h-100">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted small">{{ __('Name') }}</div>
                            <div class="fw-semibold">{{ $user->name }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">{{ __('Email') }}</div>
                            <div class="fw-semibold">{{ $user->email }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">{{ __('Phone') }}</div>
                            <div class="fw-semibold">{{ $user->phone ?: '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">{{ __('CNIC') }}</div>
                            <div class="fw-semibold">{{ $user->cnic ?: '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">{{ __('Image') }}</div>
                            <div class="fw-semibold">
                                @if ($user->image_path)
                                    <a href="{{ asset('storage/' . $user->image_path) }}" target="_blank"
                                        rel="noopener">
                                        <img src="{{ asset('storage/' . $user->image_path) }}" alt="{{ $user->name }}"
                                            style="width: 64px; height: 64px; object-fit: cover; border-radius: 6px;">
                                    </a>
                                @else
                                    -
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">{{ __('Branch') }}</div>
                            <div class="fw-semibold">{{ $user->branch?->name ?: '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">{{ __('Status') }}</div>
                            <div class="fw-semibold">{{ ucfirst($user->status->value) }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">{{ __('Created At') }}</div>
                            <div class="fw-semibold">@tenantDate($user->created_at, 'Y-m-d H:i', '')</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">{{ __('Last Login') }}</div>
                            <div class="fw-semibold">@tenantDate($user->last_login_at, 'Y-m-d H:i')</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">{{ __('Last Login IP') }}</div>
                            <div class="fw-semibold">{{ $user->last_login_ip ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('Commission Rules') }}</div>
                    <h5 class="mb-3">{{ $commissionSummary['rules_count'] }}</h5>

                    <div class="text-muted small">{{ __('Rules Payable Total') }}</div>
                    <h5 class="mb-3">{{ number_format((float) $commissionSummary['total_payable'], 2) }}</h5>

                    <div class="text-muted small">{{ __('Service Entries') }}</div>
                    <h5 class="mb-3">{{ $commissionSummary['service_entries_count'] }}</h5>

                    <div class="text-muted small">{{ __('Actual Service Payable Total') }}</div>
                    <h5 class="mb-0">{{ number_format((float) $commissionSummary['service_entries_payable_total'], 2) }}</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header">
            <h6 class="mb-0">{{ __('Commission Rules') }}</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Labour Service') }}</th>
                            <th class="text-end">{{ __('Total Amount') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th class="text-end">{{ __('Value') }}</th>
                            <th class="text-end">{{ __('Payable') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($user->commissionRules as $rule)
                            <tr>
                                <td>{{ $rule->serviceCatalog?->name ?? '-' }}</td>
                                <td class="text-end">{{ number_format((float) $rule->total_amount, 2) }}</td>
                                <td>{{ ucfirst($rule->commission_type->value) }}</td>
                                <td class="text-end">{{ number_format((float) $rule->commission_value, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $rule->payable_amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">{{ __('No commission rules found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header">
            <h6 class="mb-0">{{ __('Service Payable Details') }}</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Invoice') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Service') }}</th>
                            <th class="text-end">{{ __('Customer Charge') }}</th>
                            <th class="text-end">{{ __('Mechanic Payable') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($servicePayables as $item)
                            <tr>
                                <td>{{ $item->sale?->invoice_no ?? '-' }}</td>
                                <td>@tenantDate($item->sale?->invoice_date, 'Y-m-d')</td>
                                <td>{{ $item->description ?: ($item->serviceCatalog?->name ?? '-') }}</td>
                                <td class="text-end">{{ number_format((float) $item->line_total, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $item->mechanic_charge, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">{{ __('No service payable entries found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <x-delete-modal id="userDeleteModal" />
@endsection

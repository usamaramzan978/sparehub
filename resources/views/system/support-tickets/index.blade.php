@extends('layouts.system')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h4 class="mb-1">{{ __('Support Tickets') }}</h4>
            <p class="text-muted mb-0">{{ __('Review and respond to tenant support requests.') }}</p>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('system.support-tickets.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-6 col-xl-3">
                    <label for="tenant-id" class="form-label">{{ __('Tenant') }}</label>
                    <select id="tenant-id" name="tenant_id" class="form-select">
                        <option value="">{{ __('Select Tenant') }}</option>
                        @foreach ($tenants as $tenant)
                            <option value="{{ $tenant->id }}" @selected((string) request('tenant_id') === (string) $tenant->id)>
                                {{ $tenant->name }} ({{ $tenant->slug }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <label for="ticket-search" class="form-label">{{ __('Search') }}</label>
                    <input id="ticket-search" type="text" name="search" class="form-control" value="{{ request('search') }}"
                        placeholder="{{ __('Ticket no, title, description') }}">
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <label for="ticket-status" class="form-label">{{ __('Status') }}</label>
                    <select id="ticket-status" name="status" class="form-select">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected((string) request('status') === $status->value)>
                                {{ ucfirst(str_replace('_', ' ', $status->value)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <label for="ticket-priority" class="form-label">{{ __('Priority') }}</label>
                    <select id="ticket-priority" name="priority" class="form-select">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($priorities as $priority)
                            <option value="{{ $priority->value }}" @selected((string) request('priority') === $priority->value)>
                                {{ ucfirst($priority->value) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                </div>
                <div class="col-auto">
                    <a href="{{ route('system.support-tickets.index') }}"
                        class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('Ticket') }}</th>
                            <th>{{ __('Title') }}</th>
                            <th>{{ __('Priority') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Reported By') }}</th>
                            <th>{{ __('Created') }}</th>
                            <th>{{ __('Last Message') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (!$selectedTenant)
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    {{ __('Select a tenant to view support tickets.') }}</td>
                            </tr>
                        @else
                            @forelse ($tickets as $ticket)
                                <tr>
                                    <td>{{ $ticket['ticket_no'] }}</td>
                                    <td>{{ $ticket['title'] }}</td>
                                    <td>{{ ucfirst($ticket['priority']) }}</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $ticket['status'])) }}</td>
                                    <td>{{ $ticket['reported_by'] }}</td>
                                    <td>{{ $ticket['created_at'] }}</td>
                                    <td>{{ $ticket['last_message_at'] }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('system.support-tickets.edit', ['tenant' => $selectedTenant, 'ticket' => $ticket['id']]) }}"
                                            class="btn btn-sm btn-secondary-light">
                                            <i class="ri-edit-line"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        {{ __('No support tickets found for selected tenant.') }}</td>
                                </tr>
                            @endforelse
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

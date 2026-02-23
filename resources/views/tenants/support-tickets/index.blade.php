@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [['label' => __('Support')], ['label' => __('Support Tickets')]];
    @endphp

    <x-breadcrumb title="{{ __('Support Tickets') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.support-tickets.create') }}" class="btn btn-primary">{{ __('Create Ticket') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-body">
            <form method="GET" action="{{ route('tenant.support-tickets.index') }}" class="row g-2 mb-3">
                <div class="col-md-8">
                    <label class="form-label" for="search">{{ __('Search') }}</label>
                    <input type="text" name="search" id="search" class="form-control" value="{{ request('search') }}"
                        placeholder="{{ __('Search ticket no, title, description') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="status">{{ __('Status') }}</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                                {{ ucfirst(str_replace('_', ' ', $status->value)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100">{{ __('Filter') }}</button>
                    <a href="{{ route('tenant.support-tickets.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Ticket No') }}</th>
                            <th>{{ __('Title') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $ticket)
                            <tr>
                                <td>{{ $ticket->ticket_no }}</td>
                                <td>{{ $ticket->title }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $ticket->status->value)) }}</td>
                                <td>@tenantDate($ticket->created_at, 'Y-m-d H:i')</td>
                                <td class="text-end">
                                    <div class="btn-list">
                                        <a href="{{ route('tenant.support-tickets.show', $ticket) }}"
                                            class="btn btn-sm btn-primary-light btn-wave waves-effect waves-light"
                                            title="{{ __('Open conversation') }}">
                                            <i class="ri-eye-line"></i>
                                            {{ __('Conversation') }}
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">{{ __('No support tickets found.') }}</td>
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
@endsection

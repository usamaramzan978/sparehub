@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Support')],
            ['label' => __('Support Tickets'), 'url' => route('tenant.support-tickets.index')],
            ['label' => __('Details')],
        ];
    @endphp

    <x-breadcrumb title="{{ __('Support Ticket Details') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.support-tickets.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Ticket No') }}</div>
                        <div class="fw-semibold">{{ $supportTicket->ticket_no }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Priority') }}</div>
                        <div class="fw-semibold">{{ ucfirst($supportTicket->priority->value) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Status') }}</div>
                        <div class="fw-semibold">{{ ucfirst(str_replace('_', ' ', $supportTicket->status->value)) }}</div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Title') }}</div>
                        <div class="fw-semibold">{{ $supportTicket->title }}</div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Created At') }}</div>
                        <div>@tenantDate($supportTicket->created_at, 'Y-m-d H:i')</div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ __('Reported By') }}</div>
                        <div class="fw-semibold">{{ $supportTicket->reporter?->name ?? '-' }}</div>
                    </div>
                </div>
            </div>

            @if (! empty($supportTicket->image_paths))
                <div class="border rounded p-3 mt-3">
                    <div class="text-muted small mb-2">{{ __('Attachments') }}</div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($supportTicket->image_paths as $path)
                            @if (is_string($path) && $path !== '')
                                <a href="{{ asset('storage/' . $path) }}" target="_blank" rel="noopener">
                                    <img src="{{ asset('storage/' . $path) }}" alt="{{ __('Attachment') }}"
                                        class="rounded border" style="width: 120px; height: 120px; object-fit: cover;">
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="card custom-card border-0 shadow-sm mt-3">
        <div class="card-header">
            <h6 class="mb-0">{{ __('Conversation') }}</h6>
        </div>
        <div class="card-body">
            <div class="d-flex flex-column gap-3 mb-4">
                @forelse ($supportTicket->messages->sortBy('created_at') as $message)
                    @php
                        $isTenant = $message->sender_type === \App\Models\SupportTicketMessage::SENDER_TENANT;
                    @endphp
                    <div class="p-3 rounded border {{ $isTenant ? 'bg-light' : 'bg-primary-transparent' }}">
                        <div class="d-flex justify-content-between gap-2 mb-1">
                            <div class="fw-semibold">
                                {{ $message->sender_name }}
                                {{-- <span class="text-muted">({{ ucfirst($message->sender_type) }})</span> --}}
                            </div>
                            <small class="text-muted">@tenantDate($message->created_at, 'Y-m-d H:i')</small>
                        </div>
                        <div class="mb-0">{{ $message->message }}</div>
                    </div>
                @empty
                    <div class="text-muted">{{ __('No messages yet.') }}</div>
                @endforelse
            </div>

            <form method="POST" action="{{ route('tenant.support-tickets.messages.store', $supportTicket) }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="message">{{ __('Reply Message') }}</label>
                    <textarea id="message" name="message" rows="4" class="form-control @error('message') is-invalid @enderror"
                        required>{{ old('message') }}</textarea>
                    @error('message')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary">{{ __('Send Reply') }}</button>
            </form>
        </div>
    </div>
@endsection

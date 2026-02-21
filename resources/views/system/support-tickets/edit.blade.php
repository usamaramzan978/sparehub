@extends('layouts.system')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h4 class="mb-1">{{ __('Review Support Ticket') }}</h4>
            <p class="text-muted mb-0">{{ $tenant->name }} ({{ $tenant->slug }})</p>
        </div>
        <a href="{{ route('system.support-tickets.index', ['tenant_id' => $tenant->id]) }}"
            class="btn btn-outline-secondary">{{ __('Back') }}</a>
    </div>

    <div class="card custom-card border-0 shadow-sm h-100">
        <div class="card-body">
            <form method="POST" action="{{ route('system.support-tickets.update', ['tenant' => $tenant, 'ticket' => $ticket['id']]) }}">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="ticket-no">{{ __('Ticket No') }}</label>
                        <input id="ticket-no" type="text" class="form-control" value="{{ $ticket['ticket_no'] }}" readonly>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label" for="ticket-created">{{ __('Created At') }}</label>
                        <input id="ticket-created" type="text" class="form-control" value="{{ $ticket['created_at'] }}"
                            readonly>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label" for="ticket-reported-by">{{ __('Reported By') }}</label>
                        <input id="ticket-reported-by" type="text" class="form-control" value="{{ $ticket['reported_by'] }}"
                            readonly>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label" for="ticket-title">{{ __('Title') }}</label>
                        <input id="ticket-title" type="text" name="title" class="form-control"
                            value="{{ old('title', $ticket['title']) }}" required>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label" for="ticket-priority">{{ __('Priority') }}</label>
                        <select id="ticket-priority" name="priority" class="form-select" required>
                            @foreach ($priorities as $priority)
                                <option value="{{ $priority->value }}" @selected(old('priority', $ticket['priority']) === $priority->value)>
                                    {{ ucfirst($priority->value) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label" for="ticket-status">{{ __('Status') }}</label>
                        <select id="ticket-status" name="status" class="form-select" required>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" @selected(old('status', $ticket['status']) === $status->value)>
                                    {{ ucfirst(str_replace('_', ' ', $status->value)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="ticket-description">{{ __('Description') }}</label>
                        <textarea id="ticket-description" name="description" rows="4" class="form-control" required>{{ old('description', $ticket['description']) }}</textarea>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="fw-semibold mb-2">{{ __('Attachments') }}</div>
                    @if ($ticket['image_paths'] !== [])
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($ticket['image_paths'] as $path)
                                @if (is_string($path) && $path !== '')
                                    <a href="{{ asset('storage/' . $path) }}" target="_blank" rel="noopener">
                                        <img src="{{ asset('storage/' . $path) }}" alt="{{ __('Attachment') }}"
                                            class="rounded border" style="width: 120px; height: 120px; object-fit: cover;">
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <div class="text-muted">{{ __('No attachment uploaded.') }}</div>
                    @endif
                </div>

                <div class="mt-4 d-flex justify-content-end gap-2">
                    <a href="{{ route('system.support-tickets.index', ['tenant_id' => $tenant->id]) }}"
                        class="btn btn-light">{{ __('Cancel') }}</a>
                    <button type="submit" class="btn btn-primary">{{ __('Update Ticket') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card custom-card border-0 shadow-sm mt-3">
        <div class="card-header">
            <h6 class="mb-0">{{ __('Conversation') }}</h6>
        </div>
        <div class="card-body">
            <div class="d-flex flex-column gap-3 mb-4">
                @forelse ($ticket['messages'] as $message)
                    @php
                        $isSystem = $message['sender_type'] === \App\Models\SupportTicketMessage::SENDER_SYSTEM;
                    @endphp
                    <div class="p-3 rounded border {{ $isSystem ? 'bg-primary-transparent' : 'bg-light' }}">
                        <div class="d-flex justify-content-between gap-2 mb-1">
                            <div class="fw-semibold">
                                {{ $message['sender_name'] }}
                                <span class="text-muted">({{ ucfirst($message['sender_type']) }})</span>
                            </div>
                            <small class="text-muted">{{ $message['created_at'] }}</small>
                        </div>
                        <div>{{ $message['message'] }}</div>
                    </div>
                @empty
                    <div class="text-muted">{{ __('No messages yet.') }}</div>
                @endforelse
            </div>

            <form method="POST" action="{{ route('system.support-tickets.messages.store', ['tenant' => $tenant, 'ticket' => $ticket['id']]) }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="system-message">{{ __('Reply Message') }}</label>
                    <textarea id="system-message" name="message" rows="4" class="form-control @error('message') is-invalid @enderror"
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

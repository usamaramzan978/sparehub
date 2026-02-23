<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Enums\SupportTicketPriority;
use App\Enums\SupportTicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\System\SupportTicketMessageRequest;
use App\Http\Requests\System\SupportTicketUpdateRequest;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class SupportTicketController extends Controller
{
    public function index(Request $request): View
    {
        $tenants = Tenant::query()->orderBy('name')->get(['id', 'name', 'slug', 'status']);
        $tenantId = mb_trim($request->string('tenant_id')->toString());
        $selectedTenant = $tenantId !== '' ? Tenant::query()->find($tenantId) : null;
        $search = mb_trim($request->string('search')->toString());
        $status = mb_trim($request->string('status')->toString());
        $priority = mb_trim($request->string('priority')->toString());

        $tickets = [];
        if ($selectedTenant instanceof Tenant) {
            $tickets = $selectedTenant->run(fn (): array => SupportTicket::query()
                ->with('reporter')
                ->when(
                    $search !== '',
                    fn ($query) => $query->where(function ($builder) use ($search): void {
                        $builder
                            ->where('ticket_no', 'like', sprintf('%%%s%%', $search))
                            ->orWhere('title', 'like', sprintf('%%%s%%', $search))
                            ->orWhere('description', 'like', sprintf('%%%s%%', $search));
                    })
                )
                ->when($status !== '', fn ($query) => $query->where('status', $status))
                ->when($priority !== '', fn ($query) => $query->where('priority', $priority))
                ->latest()
                ->get()
                ->map(function (SupportTicket $ticket): array {
                    /** @var SupportTicketMessage|null $lastMessage */
                    $lastMessage = $ticket->messages()->latest()->first();
                    /** @var User|null $reporter */
                    $reporter = $ticket->reporter;

                    return [
                        'id' => $ticket->id,
                        'ticket_no' => $ticket->ticket_no,
                        'title' => $ticket->title,
                        'priority' => $ticket->priority->value,
                        'status' => $ticket->status->value,
                        'reported_by' => $reporter?->name ?? '-',
                        'created_at' => $ticket->created_at?->format('Y-m-d H:i') ?? '-',
                        'last_message_at' => $lastMessage?->created_at?->format('Y-m-d H:i') ?? '-',
                    ];
                })
                ->values()
                ->all());
        }

        return view('system.support-tickets.index', [
            'tenants' => $tenants,
            'selectedTenant' => $selectedTenant,
            'tickets' => $tickets,
            'statuses' => SupportTicketStatus::cases(),
            'priorities' => SupportTicketPriority::cases(),
        ]);
    }

    public function edit(Tenant $tenant, string $ticket): View
    {
        $ticketData = $this->resolveTicketData($tenant, $ticket);

        return view('system.support-tickets.edit', [
            'tenant' => $tenant,
            'ticket' => $ticketData,
            'statuses' => SupportTicketStatus::cases(),
            'priorities' => SupportTicketPriority::cases(),
        ]);
    }

    public function update(SupportTicketUpdateRequest $request, Tenant $tenant, string $ticket): RedirectResponse
    {
        $payload = $request->validated();

        $tenant->run(function () use ($ticket, $payload): void {
            $supportTicket = SupportTicket::query()->findOrFail($ticket);

            $supportTicket->update([
                'title' => $payload['title'],
                'description' => $payload['description'],
                'priority' => $payload['priority'],
                'status' => $payload['status'],
            ]);
        });

        return to_route('system.support-tickets.edit', ['tenant' => $tenant, 'ticket' => $ticket])
            ->with('status', 'Support ticket updated.');
    }

    public function storeMessage(SupportTicketMessageRequest $request, Tenant $tenant, string $ticket): RedirectResponse
    {
        $message = $request->validated('message');
        $systemUser = auth('system')->user();

        $tenant->run(function () use ($ticket, $message, $systemUser): void {
            $supportTicket = SupportTicket::query()->findOrFail($ticket);

            if ($supportTicket->status->value === SupportTicketStatus::OPEN->value) {
                $supportTicket->update([
                    'status' => SupportTicketStatus::IN_PROGRESS->value,
                ]);
            }

            SupportTicketMessage::query()->create([
                'support_ticket_id' => $supportTicket->id,
                'sender_type' => SupportTicketMessage::SENDER_SYSTEM,
                'sender_user_id' => (string) ($systemUser?->id ?? ''),
                'sender_name' => (string) ($systemUser?->name ?? 'System User'),
                'message' => $message,
            ]);
        });

        return to_route('system.support-tickets.edit', ['tenant' => $tenant, 'ticket' => $ticket])
            ->with('status', 'Reply sent.');
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveTicketData(Tenant $tenant, string $ticket): array
    {
        /** @var array<string, mixed> $ticketData */
        $ticketData = $tenant->run(function () use ($ticket): array {
            $supportTicket = SupportTicket::query()->with(['reporter', 'messages'])->findOrFail($ticket);
            /** @var User|null $reporter */
            $reporter = $supportTicket->reporter;

            return [
                'id' => $supportTicket->id,
                'ticket_no' => $supportTicket->ticket_no,
                'title' => $supportTicket->title,
                'description' => $supportTicket->description,
                'priority' => $supportTicket->priority->value,
                'status' => $supportTicket->status->value,
                'reported_by' => $reporter?->name ?? '-',
                'created_at' => $supportTicket->created_at?->format('Y-m-d H:i') ?? '-',
                'image_paths' => is_array($supportTicket->image_paths) ? $supportTicket->image_paths : [],
                'messages' => $supportTicket->messages
                    ->sortBy('created_at')
                    ->map(fn (SupportTicketMessage $message): array => [
                        'sender_type' => $message->sender_type,
                        'sender_name' => $message->sender_name,
                        'message' => $message->message,
                        'created_at' => $message->created_at?->format('Y-m-d H:i') ?? '-',
                    ])
                    ->values()
                    ->all(),
            ];
        });

        return $ticketData;
    }
}

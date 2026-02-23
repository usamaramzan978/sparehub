<?php

declare(strict_types=1);

namespace App\Actions\Tenant\SupportTicket;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;

final class CreateTenantSupportTicketMessageAction
{
    public function handle(SupportTicket $supportTicket, string $message): SupportTicketMessage
    {
        $user = auth('user')->user();

        return SupportTicketMessage::query()->create([
            'support_ticket_id' => $supportTicket->id,
            'sender_type' => SupportTicketMessage::SENDER_TENANT,
            'sender_user_id' => (string) ($user?->id ?? ''),
            'sender_name' => (string) ($user?->name ?? 'Tenant User'),
            'message' => $message,
        ]);
    }
}

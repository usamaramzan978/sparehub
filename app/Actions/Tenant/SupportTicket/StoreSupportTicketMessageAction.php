<?php

declare(strict_types=1);

namespace App\Actions\Tenant\SupportTicket;

use App\Enums\SupportTicketStatus;
use App\Models\SupportTicket;

final class StoreSupportTicketMessageAction
{
    public function __construct(private CreateTenantSupportTicketMessageAction $createTenantSupportTicketMessageAction) {}

    public function handle(SupportTicket $supportTicket, string $message): void
    {
        if (in_array($supportTicket->status->value, [SupportTicketStatus::RESOLVED->value, SupportTicketStatus::CLOSED->value], true)) {
            $supportTicket->update(['status' => SupportTicketStatus::OPEN->value]);
        }

        $this->createTenantSupportTicketMessageAction->handle($supportTicket, $message);
    }
}

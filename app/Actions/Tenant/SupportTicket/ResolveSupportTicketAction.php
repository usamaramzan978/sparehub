<?php

declare(strict_types=1);

namespace App\Actions\Tenant\SupportTicket;

use App\Models\SupportTicket;

final class ResolveSupportTicketAction
{
    public function handle(string $supportTicketId): SupportTicket
    {
        return SupportTicket::query()->withoutGlobalScopes()->findOrFail($supportTicketId);
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Tenant\SupportTicket;

use App\Models\SupportTicket;
use Illuminate\Support\Str;

final class GenerateSupportTicketNumberAction
{
    public function handle(string $branchId): string
    {
        do {
            $ticketNumber = sprintf('SUP-%s', Str::upper(Str::random(8)));
            $exists = SupportTicket::query()
                ->withoutGlobalScopes()
                ->where('branch_id', $branchId)
                ->where('ticket_no', $ticketNumber)
                ->exists();
        } while ($exists);

        return $ticketNumber;
    }
}

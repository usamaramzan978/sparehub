<?php

declare(strict_types=1);

namespace App\Actions\Tenant\SupportTicket;

use App\Models\SupportTicket;

final class EnsureSupportTicketInBranchAction
{
    public function handle(SupportTicket $supportTicket, string $branchId): SupportTicket
    {
        abort_if($supportTicket->branch_id !== $branchId, 404);

        return $supportTicket;
    }
}

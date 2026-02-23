<?php

declare(strict_types=1);

namespace App\Actions\Tenant\SupportTicket;

use App\Enums\SupportTicketPriority;
use App\Enums\SupportTicketStatus;
use App\Models\SupportTicket;

final class CreateSupportTicketAction
{
    public function __construct(
        private GenerateSupportTicketNumberAction $generateSupportTicketNumberAction,
        private ReplaceSupportTicketImagesAction $replaceSupportTicketImagesAction,
        private CreateTenantSupportTicketMessageAction $createTenantSupportTicketMessageAction,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, string $branchId, mixed $uploadedImages): SupportTicket
    {
        $ticket = SupportTicket::query()->create([
            'branch_id' => $branchId,
            'reported_by' => auth('user')->id(),
            'ticket_no' => $this->generateSupportTicketNumberAction->handle($branchId),
            'title' => $payload['title'],
            'description' => $payload['description'],
            'priority' => $payload['priority'] ?? SupportTicketPriority::MEDIUM->value,
            'status' => $payload['status'] ?? SupportTicketStatus::OPEN->value,
            'image_paths' => [],
        ]);

        $this->replaceSupportTicketImagesAction->handle($ticket, $uploadedImages);
        $this->createTenantSupportTicketMessageAction->handle($ticket, (string) $payload['description']);

        return $ticket;
    }
}

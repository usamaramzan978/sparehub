<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SupportTicketPriority;
use App\Enums\SupportTicketStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\SupportTicket>
 */
final class SupportTicketFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => (string) Str::uuid(),
            'reported_by' => (string) Str::uuid(),
            'ticket_no' => sprintf('SUP-%s', Str::upper(Str::random(8))),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'priority' => $this->faker->randomElement([
                SupportTicketPriority::LOW->value,
                SupportTicketPriority::MEDIUM->value,
                SupportTicketPriority::HIGH->value,
            ]),
            'status' => $this->faker->randomElement([
                SupportTicketStatus::OPEN->value,
                SupportTicketStatus::IN_PROGRESS->value,
                SupportTicketStatus::RESOLVED->value,
                SupportTicketStatus::CLOSED->value,
            ]),
            'image_paths' => [],
        ];
    }
}

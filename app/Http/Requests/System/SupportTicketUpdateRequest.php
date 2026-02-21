<?php

declare(strict_types=1);

namespace App\Http\Requests\System;

use App\Enums\SupportTicketPriority;
use App\Enums\SupportTicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SupportTicketUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string'],
            'priority' => ['required', Rule::enum(SupportTicketPriority::class)],
            'status' => ['required', Rule::enum(SupportTicketStatus::class)],
        ];
    }
}

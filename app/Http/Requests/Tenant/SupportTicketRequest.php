<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\SupportTicketPriority;
use App\Enums\SupportTicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SupportTicketRequest extends FormRequest
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
            'priority' => ['nullable', Rule::enum(SupportTicketPriority::class)],
            'status' => ['nullable', Rule::enum(SupportTicketStatus::class)],
            'images' => ['nullable', 'array', 'max:3'],
            'images.*' => ['file', 'mimes:png', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Ticket title is required.',
            'description.required' => 'Ticket description is required.',
            'images.max' => 'You can upload a maximum of 3 images.',
            'images.*.mimes' => 'Each attachment must be a PNG image.',
        ];
    }
}

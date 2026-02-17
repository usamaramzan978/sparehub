<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

final class DashboardFilterRequest extends FormRequest
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
            'date_range' => ['nullable', 'string', 'max:40'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date_to.after_or_equal' => 'The end date must be after or equal to the start date.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $dateRange = mb_trim((string) $this->input('date_range', ''));

        if ($dateRange === '' || ! str_contains($dateRange, ' to ')) {
            return;
        }

        $parts = explode(' to ', $dateRange);

        if (count($parts) !== 2) {
            return;
        }

        [$dateFrom, $dateTo] = $parts;

        $this->merge([
            'date_from' => mb_trim($dateFrom),
            'date_to' => mb_trim($dateTo),
        ]);
    }
}

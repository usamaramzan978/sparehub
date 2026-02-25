<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Date;
use Throwable;

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
        $dateFrom = mb_trim((string) $this->input('date_from', ''));
        $dateTo = mb_trim((string) $this->input('date_to', ''));
        $dateRange = mb_trim((string) $this->input('date_range', ''));

        if ($dateRange !== '' && str_contains($dateRange, ' to ')) {
            $parts = explode(' to ', $dateRange);
            if (count($parts) === 2) {
                [$dateFrom, $dateTo] = $parts;
                $dateFrom = mb_trim($dateFrom);
                $dateTo = mb_trim($dateTo);
            }
        }

        $this->merge([
            'date_from' => $this->normalizeDateValue($dateFrom),
            'date_to' => $this->normalizeDateValue($dateTo),
        ]);
    }

    private function normalizeDateValue(string $dateValue): string
    {
        if ($dateValue === '') {
            return '';
        }

        $knownFormats = ['Y-m-d', 'F, d Y', 'M, d Y', 'd M Y', 'd-m-Y', 'm/d/Y'];

        foreach ($knownFormats as $knownFormat) {
            try {
                return Date::createFromFormat($knownFormat, $dateValue)->toDateString();
            } catch (Throwable) {
                continue;
            }
        }

        try {
            return Date::parse($dateValue)->toDateString();
        } catch (Throwable) {
            return $dateValue;
        }
    }
}

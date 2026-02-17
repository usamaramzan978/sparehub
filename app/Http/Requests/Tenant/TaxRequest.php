<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TaxRequest extends FormRequest
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
        $taxId = $this->route('tax')?->id;

        return [
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('taxes', 'code')
                    ->ignore($taxId),
            ],
            'name' => ['required', 'string', 'max:100'],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_inclusive' => ['nullable', 'boolean'],
            'status' => ['required', Rule::enum(RecordStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Tax code is required.',
            'code.unique' => 'This tax code already exists.',
            'name.required' => 'Tax name is required.',
            'rate.required' => 'Tax rate is required.',
            'status.required' => 'Please select a tax status.',
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\System;

use App\Enums\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $planId = $this->route('plan')?->id;

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('plans', 'code')->ignore($planId)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'annual_price' => ['nullable', 'numeric', 'min:0'],
            'max_users' => ['nullable', 'integer', 'min:1'],
            'max_branches' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', Rule::enum(RecordStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'This plan code is already in use.',
            'monthly_price.min' => 'Monthly price must be zero or greater.',
            'annual_price.min' => 'Annual price must be zero or greater.',
        ];
    }
}

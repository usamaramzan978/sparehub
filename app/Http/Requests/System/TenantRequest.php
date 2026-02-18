<?php

declare(strict_types=1);

namespace App\Http\Requests\System;

use App\Enums\TenantStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isCreate = $this->isMethod('post');

        return [
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(TenantStatus::class)],
            'plan_id' => ['nullable', 'integer', Rule::exists('plans', 'id')],
            'owner_name' => [$isCreate ? 'required' : 'nullable', 'string', 'max:255'],
            'owner_email' => [$isCreate ? 'required' : 'nullable', 'email', 'max:255'],
            'owner_phone' => ['nullable', 'string', 'max:50'],
            'owner_password' => [$isCreate ? 'required' : 'nullable', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'owner_name.required' => 'Owner name is required.',
            'owner_email.required' => 'Owner email is required.',
            'owner_password.required' => 'Owner password is required.',
            'owner_password.min' => 'Owner password must be at least 8 characters.',
            'owner_password.confirmed' => 'Owner password confirmation does not match.',
        ];
    }
}

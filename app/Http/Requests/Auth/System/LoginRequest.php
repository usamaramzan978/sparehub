<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth\System;

use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
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
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    /**
     *  array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'The :attribute field is required.',
            'email' => 'The :attribute must be a valid email address.',
            'uuid' => 'The :attribute must be a valid UUID.',
            'date' => 'The :attribute must be a valid date.',
            'numeric' => 'The :attribute must be a valid number.',
            'min' => 'The :attribute is below the minimum allowed value.',
            'max' => 'The :attribute exceeds the maximum allowed value.',
            'in' => 'The selected :attribute is invalid.',
            'exists' => 'The selected :attribute is invalid.',
            'unique' => 'The :attribute has already been taken.',
        ];
    }
}

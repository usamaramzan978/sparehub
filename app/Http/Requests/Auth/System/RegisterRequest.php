<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth\System;

use Illuminate\Foundation\Http\FormRequest;

final class RegisterRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:system_users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}

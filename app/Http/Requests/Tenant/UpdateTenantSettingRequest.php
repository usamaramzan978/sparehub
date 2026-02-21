<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\TwoFactorMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateTenantSettingRequest extends FormRequest
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
            'company_name' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'support_phone' => ['nullable', 'string', 'max:50'],
            'timezone' => ['nullable', 'timezone:all'],
            'email_notifications_enabled' => ['nullable', 'boolean'],
            'two_factor_enabled' => ['nullable', 'boolean'],
            'two_factor_method' => [
                'nullable',
                'required_if:two_factor_enabled,1',
                Rule::enum(TwoFactorMethod::class),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'logo.image' => 'Logo must be a valid image file.',
            'logo.max' => 'Logo must be less than 2MB.',
            'support_email.email' => 'Support email must be a valid email address.',
            'two_factor_method.required_if' => 'Two-factor method is required when two-factor authentication is enabled.',
        ];
    }
}

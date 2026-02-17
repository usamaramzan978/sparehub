<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

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
            'notify_email' => ['nullable', 'boolean'],
            'enable_otp' => ['nullable', 'boolean'],
            'otp_length' => ['nullable', 'integer', 'between:4,10'],
            'otp_expiry_minutes' => ['nullable', 'integer', 'between:1,120'],
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
            'otp_length.between' => 'OTP length must be between 4 and 10 digits.',
            'otp_expiry_minutes.between' => 'OTP expiry must be between 1 and 120 minutes.',
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth\Tenant;

use Illuminate\Foundation\Http\FormRequest;

final class TwoStepVerificationRequest extends FormRequest
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
            'code' => ['required', 'array', 'size:4'],
            'code.*' => ['required', 'digits:1'],
        ];
    }
}

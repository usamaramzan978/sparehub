<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\CommissionType;
use App\Enums\ServiceCatalogType;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UserRequest extends FormRequest
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
        $userRouteParam = $this->route('user');
        $userId = $userRouteParam instanceof User
            ? $userRouteParam->id
            : (is_string($userRouteParam) ? $userRouteParam : null);
        $isCreate = $this->isMethod('post');
        $branchId = (string) session('tenant.current_branch_id');
        $serviceCatalogExists = Rule::exists('service_catalog', 'id')->where(
            fn ($query) => $query
                ->where('branch_id', $branchId)
                ->where('type', ServiceCatalogType::Labour->value)
        );

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => [$isCreate ? 'required' : 'nullable', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:25'],
            'cnic' => ['nullable', 'string', 'max:25'],
            'image' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'commission_rules' => ['nullable', 'array'],
            'commission_rules.*.service_catalog_id' => ['required', 'uuid', $serviceCatalogExists],
            'commission_rules.*.total_amount' => ['required', 'numeric', 'min:0'],
            'commission_rules.*.commission_type' => ['required', Rule::enum(CommissionType::class)],
            'commission_rules.*.commission_value' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'User name is required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already in use.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'status.required' => 'Please select a user status.',
            'image.mimes' => 'Image must be a jpeg, jpg, png, or webp file.',
            'image.max' => 'Image size must not exceed 2 MB.',
        ];
    }
}

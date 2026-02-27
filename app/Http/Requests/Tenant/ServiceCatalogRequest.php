<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\RecordStatus;
use App\Enums\ServiceCatalogType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ServiceCatalogRequest extends FormRequest
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
        $serviceCatalogId = $this->route('service_catalog')?->id;
        $branchId = session('tenant.current_branch_id');

        return [
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('service_catalog', 'code')
                    ->where(fn ($query) => $query->where('branch_id', $branchId))
                    ->ignore($serviceCatalogId),
            ],
            'name' => ['required', 'string', 'max:160'],
            'type' => ['required', Rule::enum(ServiceCatalogType::class)],
            'default_tax_id' => ['nullable', 'uuid', Rule::exists('taxes', 'id')],
            'base_price' => ['required', 'numeric', 'min:0'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'status' => ['required', Rule::enum(RecordStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Service code is required.',
            'code.unique' => 'This service code already exists for the current branch.',
            'name.required' => 'Service name is required.',
            'base_price.required' => 'Base price is required.',
            'status.required' => 'Please select a status.',
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\BranchStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class BranchRequest extends FormRequest
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
        $branchId = $this->route('branch')?->id;

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('branches', 'code')->ignore($branchId)],
            'name' => ['required', 'string', 'max:255'],
            'warehouse_id' => [
                'nullable',
                'uuid',
                Rule::exists('warehouses', 'id'),
            ],
            'status' => ['required', Rule::enum(BranchStatus::class)],
        ];
    }
}

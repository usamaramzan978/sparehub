<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class WarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $warehouseId = $this->route('warehouse')?->id;
        $branchId = session('tenant.current_branch_id');
        $codeUnique = Rule::unique('warehouses', 'code')->ignore($warehouseId);

        if (is_string($branchId) && $branchId !== '') {
            $codeUnique = $codeUnique->where(fn ($query) => $query->where('branch_id', $branchId));
        }

        return [
            'code' => ['required', 'string', 'max:30', $codeUnique],
            'name' => ['required', 'string', 'max:120'],
            'status' => ['required', Rule::enum(RecordStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Warehouse code is required.',
            'code.unique' => 'This warehouse code already exists for current branch.',
            'name.required' => 'Warehouse name is required.',
            'status.required' => 'Please select a warehouse status.',
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\PaymentMethodType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ExpenseRequest extends FormRequest
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
        $branchId = session('tenant.current_branch_id');
        $expenseId = $this->route('expense');
        if (! is_string($expenseId) && ! is_null($expenseId)) {
            $expenseId = $expenseId->id ?? null;
        }

        $uniqueReference = Rule::unique('expenses', 'reference_no')->ignore($expenseId);
        if (is_string($branchId) && $branchId !== '') {
            $uniqueReference = $uniqueReference->where(fn ($query) => $query->where('branch_id', $branchId));
        }

        return [
            'title' => ['required', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:80'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', Rule::enum(PaymentMethodType::class)],
            'expense_date' => ['required', 'date'],
            'reference_no' => ['nullable', 'string', 'max:60', $uniqueReference],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Expense title is required.',
            'amount.required' => 'Expense amount is required.',
            'amount.gt' => 'Expense amount must be greater than zero.',
            'payment_method.required' => 'Please select a payment method.',
            'expense_date.required' => 'Expense date is required.',
            'reference_no.unique' => 'This reference number already exists for this branch.',
        ];
    }
}

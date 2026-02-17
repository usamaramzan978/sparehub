<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\InvoiceType;
use App\Enums\SaleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaleRequest extends FormRequest
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
        $saleId = $this->route('sale')?->id;
        $branchId = session('tenant.current_branch_id');
        $invoiceUnique = Rule::unique('sales', 'invoice_no')->ignore($saleId);
        $customerExists = Rule::exists('customers', 'id');
        $jobCardExists = Rule::exists('job_cards', 'id');

        if (is_string($branchId) && $branchId !== '') {
            $invoiceUnique = $invoiceUnique->where(fn ($query) => $query->where('branch_id', $branchId));
            $customerExists = $customerExists->where(fn ($query) => $query->where('branch_id', $branchId));
            $jobCardExists = $jobCardExists->where(fn ($query) => $query->where('branch_id', $branchId));
        }

        return [
            'customer_id' => ['nullable', 'uuid', $customerExists],
            'job_card_id' => ['nullable', 'uuid', $jobCardExists],
            'invoice_no' => ['required', 'string', 'max:40', $invoiceUnique],
            'invoice_date' => ['required', 'date'],
            'status' => ['required', Rule::enum(SaleStatus::class)],
            'invoice_type' => ['required', Rule::enum(InvoiceType::class)],
            'sub_total' => ['nullable', 'numeric', 'min:0'],
            'discount_total' => ['nullable', 'numeric', 'min:0'],
            'tax_total' => ['nullable', 'numeric', 'min:0'],
            'grand_total' => ['nullable', 'numeric', 'min:0'],
            'paid_total' => ['nullable', 'numeric', 'min:0'],
            'balance_due' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
            'posted_at' => ['nullable', 'date'],
        ];
    }
}

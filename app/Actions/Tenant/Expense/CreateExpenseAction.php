<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Expense;

use App\Models\Expense;
use App\Support\AuditTimelineLogger;
use BackedEnum;
use Illuminate\Support\Facades\Auth;

final class CreateExpenseAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, string $branchId, mixed $createdBy): Expense
    {
        $payload['branch_id'] = $branchId;
        $payload['created_by'] = $createdBy;

        $expense = Expense::query()->create($payload);

        AuditTimelineLogger::log(
            event: 'expense_created',
            description: 'Expense recorded.',
            causer: Auth::guard('user')->user(),
            subject: $expense,
            properties: [
                'expense_id' => (string) $expense->id,
                'title' => (string) $expense->title,
                'amount' => (float) $expense->amount,
                'method' => $this->paymentMethodValue($expense->payment_method),
            ],
        );

        return $expense;
    }

    private function paymentMethodValue(mixed $method): string
    {
        if ($method instanceof BackedEnum) {
            return (string) $method->value;
        }

        return (string) $method;
    }
}

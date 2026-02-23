<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Expense;

use App\Models\Expense;
use App\Support\AuditTimelineLogger;
use Illuminate\Support\Facades\Auth;

final class UpdateExpenseAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(string $expenseId, array $payload, string $currentBranchId): bool
    {
        $expense = Expense::query()->withoutGlobalScopes()->findOrFail($expenseId);

        abort_if($expense->branch_id !== $currentBranchId, 404);

        $updated = $expense->update($payload);

        AuditTimelineLogger::log(
            event: 'expense_updated',
            description: 'Expense updated.',
            causer: Auth::guard('user')->user(),
            subject: $expense,
            properties: [
                'expense_id' => (string) $expense->id,
                'title' => (string) $expense->title,
                'amount' => (float) $expense->amount,
                'changed_attributes' => array_keys($payload),
            ],
        );

        return $updated;
    }
}

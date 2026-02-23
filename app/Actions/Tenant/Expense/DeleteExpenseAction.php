<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Expense;

use App\Models\Expense;
use App\Support\AuditTimelineLogger;
use Illuminate\Support\Facades\Auth;

final class DeleteExpenseAction
{
    public function handle(string $expenseId, string $currentBranchId): bool
    {
        $expense = Expense::query()->withoutGlobalScopes()->findOrFail($expenseId);

        abort_if($expense->branch_id !== $currentBranchId, 404);

        $snapshot = [
            'expense_id' => (string) $expense->id,
            'title' => (string) $expense->title,
            'amount' => (float) $expense->amount,
        ];

        $deleted = (bool) $expense->delete();

        AuditTimelineLogger::log(
            event: 'expense_deleted',
            description: 'Expense deleted.',
            causer: Auth::guard('user')->user(),
            subject: $expense,
            properties: $snapshot,
        );

        return $deleted;
    }
}

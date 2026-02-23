<?php

declare(strict_types=1);

namespace App\Actions\Tenant\EmployeeSalary;

use App\Models\EmployeeSalary;
use Illuminate\Support\Facades\Date;

final class UpsertEmployeeSalaryAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, string $branchId): EmployeeSalary
    {
        $salaryMonth = Date::parse((string) $payload['salary_month'])->startOfMonth()->toDateString();
        $basicSalary = (float) $payload['basic_salary'];
        $bonus = (float) ($payload['bonus'] ?? 0);
        $deduction = (float) ($payload['deduction'] ?? 0);
        $netSalary = max(($basicSalary + $bonus) - $deduction, 0);

        $record = EmployeeSalary::query()->updateOrCreate(
            [
                'branch_id' => $branchId,
                'user_id' => $payload['user_id'],
                'salary_month' => $salaryMonth,
            ],
            [
                'basic_salary' => $basicSalary,
                'bonus' => $bonus,
                'deduction' => $deduction,
                'net_salary' => $netSalary,
                'notes' => $payload['notes'] ?? null,
            ]
        );

        if ($payload['action'] === 'mark_paid') {
            $record->update(['paid_at' => now()]);
        }

        return $record;
    }
}

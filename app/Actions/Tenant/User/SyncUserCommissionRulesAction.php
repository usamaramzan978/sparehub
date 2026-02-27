<?php

declare(strict_types=1);

namespace App\Actions\Tenant\User;

use App\Enums\CommissionType;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class SyncUserCommissionRulesAction
{
    /**
     * @param  array<int, array<string, mixed>>  $rules
     */
    public function handle(User $user, array $rules): void
    {
        DB::connection('tenant')->transaction(function () use ($user, $rules): void {
            $user->commissionRules()->delete();

            foreach ($rules as $index => $rule) {
                $totalAmount = (float) ($rule['total_amount'] ?? 0);
                $commissionValue = (float) ($rule['commission_value'] ?? 0);
                $commissionType = (string) ($rule['commission_type'] ?? CommissionType::FIXED->value);

                $payableAmount = $commissionType === CommissionType::PERCENTAGE->value
                    ? ($totalAmount * $commissionValue) / 100
                    : $commissionValue;

                $user->commissionRules()->create([
                    'service_catalog_id' => (string) ($rule['service_catalog_id'] ?? ''),
                    'total_amount' => $totalAmount,
                    'commission_type' => $commissionType,
                    'commission_value' => $commissionValue,
                    'payable_amount' => round($payableAmount, 2),
                    'sort_order' => $index,
                ]);
            }
        });
    }
}

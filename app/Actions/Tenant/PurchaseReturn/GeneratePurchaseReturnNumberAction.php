<?php

declare(strict_types=1);

namespace App\Actions\Tenant\PurchaseReturn;

use App\Models\PurchaseReturn;

final class GeneratePurchaseReturnNumberAction
{
    public function handle(string $branchId): string
    {
        $datePrefix = now()->format('Ymd');
        $base = 'PRET-'.$datePrefix.'-';

        do {
            $last = PurchaseReturn::query()
                ->withoutGlobalScopes()
                ->withTrashed()
                ->where('branch_id', $branchId)
                ->where('return_no', 'like', $base.'%')
                ->orderByDesc('return_no')
                ->value('return_no');

            $next = 1;
            if (is_string($last)) {
                $suffix = (int) mb_substr($last, mb_strlen($base));
                $next = $suffix + 1;
            }

            $returnNumber = $base.mb_str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $exists = PurchaseReturn::query()
                ->withoutGlobalScopes()
                ->withTrashed()
                ->where('branch_id', $branchId)
                ->where('return_no', $returnNumber)
                ->exists();
        } while ($exists);

        return $returnNumber;
    }
}

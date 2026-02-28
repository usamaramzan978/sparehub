<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Purchase;

use App\Models\Purchase;

final class GeneratePurchaseNumberAction
{
    public function handle(string $branchId): string
    {
        $datePrefix = now()->format('Ymd');
        $base = 'PUR-'.$datePrefix.'-';

        do {
            $last = Purchase::query()
                ->withoutGlobalScopes()
                ->withTrashed()
                ->where('branch_id', $branchId)
                ->where('purchase_no', 'like', $base.'%')
                ->orderByDesc('purchase_no')
                ->value('purchase_no');

            $next = 1;
            if (is_string($last)) {
                $suffix = (int) mb_substr($last, mb_strlen($base));
                $next = $suffix + 1;
            }

            $purchaseNumber = $base.mb_str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $exists = Purchase::query()
                ->withoutGlobalScopes()
                ->withTrashed()
                ->where('branch_id', $branchId)
                ->where('purchase_no', $purchaseNumber)
                ->exists();
        } while ($exists);

        return $purchaseNumber;
    }
}

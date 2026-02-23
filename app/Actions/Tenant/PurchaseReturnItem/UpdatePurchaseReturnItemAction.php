<?php

declare(strict_types=1);

namespace App\Actions\Tenant\PurchaseReturnItem;

use App\Actions\Tenant\PurchaseReturn\SyncPurchaseReturnItemStocksAction;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;

final readonly class UpdatePurchaseReturnItemAction
{
    public function __construct(
        private SyncPurchaseReturnItemStocksAction $syncPurchaseReturnItemStocksAction,
        private RecalculatePurchaseReturnTotalsAction $recalculatePurchaseReturnTotalsAction,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(PurchaseReturnItem $purchaseReturnItem, array $payload): bool
    {
        $payload['line_total'] = ((float) $payload['qty'] * (float) $payload['unit_cost']) + (float) ($payload['tax_amount'] ?? 0);

        $purchaseReturn = $purchaseReturnItem->purchaseReturn;
        abort_if(! $purchaseReturn instanceof PurchaseReturn, 404);

        $this->syncPurchaseReturnItemStocksAction->handle(collect([$purchaseReturnItem]), $purchaseReturn->branch_id, reverse: true);
        $updated = $purchaseReturnItem->update($payload);
        $this->syncPurchaseReturnItemStocksAction->handle(collect([$purchaseReturnItem]), $purchaseReturn->branch_id);
        $this->recalculatePurchaseReturnTotalsAction->handle($purchaseReturn);

        return $updated;
    }
}

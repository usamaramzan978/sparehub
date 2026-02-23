<?php

declare(strict_types=1);

namespace App\Actions\Tenant\PurchaseReturnItem;

use App\Actions\Tenant\PurchaseReturn\SyncPurchaseReturnItemStocksAction;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;

final readonly class DeletePurchaseReturnItemAction
{
    public function __construct(
        private SyncPurchaseReturnItemStocksAction $syncPurchaseReturnItemStocksAction,
        private RecalculatePurchaseReturnTotalsAction $recalculatePurchaseReturnTotalsAction,
    ) {}

    public function handle(PurchaseReturnItem $purchaseReturnItem): bool
    {
        $purchaseReturn = $purchaseReturnItem->purchaseReturn;
        abort_if(! $purchaseReturn instanceof PurchaseReturn, 404);

        $this->syncPurchaseReturnItemStocksAction->handle(collect([$purchaseReturnItem]), $purchaseReturn->branch_id, reverse: true);
        $deleted = (bool) $purchaseReturnItem->delete();
        $this->recalculatePurchaseReturnTotalsAction->handle($purchaseReturn);

        return $deleted;
    }
}

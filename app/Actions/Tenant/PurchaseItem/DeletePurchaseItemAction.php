<?php

declare(strict_types=1);

namespace App\Actions\Tenant\PurchaseItem;

use App\Actions\Tenant\Purchase\SyncPurchaseItemStocksAction;
use App\Models\PurchaseItem;

final readonly class DeletePurchaseItemAction
{
    public function __construct(
        private SyncPurchaseItemStocksAction $syncPurchaseItemStocksAction,
        private RecalculatePurchaseTotalsAction $recalculatePurchaseTotalsAction,
    ) {}

    public function handle(PurchaseItem $purchaseItem): bool
    {
        $purchase = $purchaseItem->purchase;

        $this->syncPurchaseItemStocksAction->handle(collect([$purchaseItem]), $purchaseItem->branch_id, reverse: true);
        $deleted = (bool) $purchaseItem->delete();
        $this->recalculatePurchaseTotalsAction->handle($purchase);

        return $deleted;
    }
}

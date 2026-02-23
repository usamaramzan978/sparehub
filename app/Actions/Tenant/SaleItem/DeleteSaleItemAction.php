<?php

declare(strict_types=1);

namespace App\Actions\Tenant\SaleItem;

use App\Actions\Tenant\Sale\SyncSaleItemStocksAction;
use App\Models\SaleItem;

final readonly class DeleteSaleItemAction
{
    public function __construct(
        private SyncSaleItemStocksAction $syncSaleItemStocksAction,
        private RecalculateSaleTotalsAction $recalculateSaleTotalsAction,
    ) {}

    public function handle(SaleItem $saleItem): bool
    {
        $sale = $saleItem->sale;

        $this->syncSaleItemStocksAction->handle(collect([$saleItem]), $saleItem->branch_id, reverse: true);
        $deleted = (bool) $saleItem->delete();
        $this->recalculateSaleTotalsAction->handle($sale);

        return $deleted;
    }
}

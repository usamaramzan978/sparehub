<?php

declare(strict_types=1);

namespace App\Actions\Tenant\SaleItem;

use App\Actions\Tenant\Sale\SyncSaleItemStocksAction;
use App\Models\SaleItem;

final readonly class UpdateSaleItemAction
{
    public function __construct(
        private SyncSaleItemStocksAction $syncSaleItemStocksAction,
        private RecalculateSaleTotalsAction $recalculateSaleTotalsAction,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(SaleItem $saleItem, array $payload, string $branchId): bool
    {
        $payload['branch_id'] = $branchId;
        $payload['line_total'] = ((float) $payload['qty'] * (float) $payload['unit_price']) - (float) ($payload['discount_amount'] ?? 0) + (float) ($payload['tax_amount'] ?? 0);

        $originalItem = clone $saleItem;
        $this->syncSaleItemStocksAction->handle(collect([$originalItem]), $payload['branch_id'], reverse: true);
        $updated = $saleItem->update($payload);
        $this->syncSaleItemStocksAction->handle(collect([$saleItem]), $payload['branch_id']);
        $this->recalculateSaleTotalsAction->handle($saleItem->sale);

        return $updated;
    }
}

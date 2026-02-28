<?php

declare(strict_types=1);

namespace App\Actions\Tenant\SaleItem;

use App\Actions\Tenant\Sale\SyncSaleItemStocksAction;
use App\Models\SaleItem;

final readonly class CreateSaleItemAction
{
    public function __construct(
        private SyncSaleItemStocksAction $syncSaleItemStocksAction,
        private RecalculateSaleTotalsAction $recalculateSaleTotalsAction,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, string $branchId): SaleItem
    {
        $payload['branch_id'] = $branchId;
        $payload['line_total'] = ((float) $payload['qty'] * (float) $payload['unit_price']) - (float) ($payload['discount_amount'] ?? 0) + (float) ($payload['tax_amount'] ?? 0);

        $saleItem = SaleItem::query()->create($payload);
        $this->syncSaleItemStocksAction->handle(collect([$saleItem]), $payload['branch_id']);
        $this->recalculateSaleTotalsAction->handle($saleItem->sale);

        return $saleItem;
    }
}

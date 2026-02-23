<?php

declare(strict_types=1);

namespace App\Actions\Tenant\PurchaseItem;

use App\Actions\Tenant\Purchase\SyncPurchaseItemStocksAction;
use App\Models\PurchaseItem;

final readonly class CreatePurchaseItemAction
{
    public function __construct(
        private SyncPurchaseItemStocksAction $syncPurchaseItemStocksAction,
        private RecalculatePurchaseTotalsAction $recalculatePurchaseTotalsAction,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, string $branchId): PurchaseItem
    {
        $payload['branch_id'] = $branchId;
        $payload['received_qty'] ??= 0;
        $payload['line_total'] = ((float) $payload['qty'] * (float) $payload['unit_cost']) - (float) ($payload['discount_amount'] ?? 0) + (float) ($payload['tax_amount'] ?? 0);

        $item = PurchaseItem::query()->create($payload);
        $this->syncPurchaseItemStocksAction->handle(collect([$item]), $payload['branch_id']);
        $this->recalculatePurchaseTotalsAction->handle($item->purchase);

        return $item;
    }
}

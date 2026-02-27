<?php

declare(strict_types=1);

namespace App\Actions\Tenant\SaleReturn;

use App\Models\SaleReturn;
use App\Models\SaleReturnItem;

final readonly class SyncSaleReturnItemsAction
{
    public function __construct(private SyncSaleReturnItemStocksAction $syncSaleReturnItemStocksAction) {}

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function handle(SaleReturn $saleReturn, array $items): void
    {
        $existingItems = $saleReturn->items()->get();
        $this->syncSaleReturnItemStocksAction->handle($existingItems, $saleReturn->branch_id, reverse: true);
        $saleReturn->items()->delete();

        foreach ($items as $item) {
            $qty = (float) $item['qty'];
            $unitPrice = (float) $item['unit_price'];
            $taxAmount = (float) ($item['tax_amount'] ?? 0);
            $lineTotal = ($qty * $unitPrice) + $taxAmount;

            SaleReturnItem::query()->create([
                'sale_return_id' => $saleReturn->id,
                'sale_item_id' => ($item['sale_item_id'] ?? null) ?: null,
                'product_id' => $item['product_id'],
                'tax_id' => ($item['tax_id'] ?? null) ?: null,
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'tax_amount' => $taxAmount,
                'line_total' => $lineTotal,
                'remarks' => $item['remarks'] ?? null,
            ]);
        }

        $createdItems = $saleReturn->items()->get();
        $this->syncSaleReturnItemStocksAction->handle($createdItems, $saleReturn->branch_id);
        $subTotal = (float) $createdItems->sum(fn (SaleReturnItem $item): float => (float) $item->qty * (float) $item->unit_price);
        $taxTotal = (float) $createdItems->sum(fn (SaleReturnItem $item): float => (float) $item->tax_amount);
        $grandTotal = (float) $createdItems->sum(fn (SaleReturnItem $item): float => (float) $item->line_total);

        $saleReturn->update([
            'sub_total' => $subTotal,
            'tax_total' => $taxTotal,
            'grand_total' => $grandTotal,
        ]);
    }
}

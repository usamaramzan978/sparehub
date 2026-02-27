<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Sale;

use App\Models\Sale;
use App\Models\SaleItem;

final readonly class SyncSaleItemsAction
{
    public function __construct(private SyncSaleItemStocksAction $syncSaleItemStocksAction) {}

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function handle(Sale $sale, array $items, string $branchId): void
    {
        $existingItems = $sale->items()->get();
        $this->syncSaleItemStocksAction->handle($existingItems, $branchId, reverse: true);
        $sale->items()->delete();

        foreach ($items as $item) {
            $qty = (float) $item['qty'];
            $unitPrice = (float) $item['unit_price'];
            $discountAmount = (float) ($item['discount_amount'] ?? 0);
            $taxAmount = (float) ($item['tax_amount'] ?? 0);
            $lineTotal = ($qty * $unitPrice) - $discountAmount + $taxAmount;

            $lineType = (string) $item['line_type'];
            $productId = $lineType === 'product' ? $item['product_id'] : null;
            $serviceCatalogId = $lineType === 'service' ? $item['service_catalog_id'] : null;
            $jobCardServiceId = $lineType === 'service' ? ($item['job_card_service_id'] ?? null) : null;
            $mechanicId = $lineType === 'service' ? ($item['mechanic_id'] ?? null) : null;
            $mechanicCharge = $lineType === 'service' ? (float) ($item['mechanic_charge'] ?? 0) : 0.0;

            SaleItem::query()->create([
                'sale_id' => $sale->id,
                'branch_id' => $branchId,
                'product_id' => $productId,
                'service_catalog_id' => $serviceCatalogId,
                'job_card_service_id' => $jobCardServiceId,
                'mechanic_id' => $mechanicId,
                'line_type' => $lineType,
                'description' => $item['description'] ?? null,
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'mechanic_charge' => $mechanicCharge,
                'line_total' => $lineTotal,
            ]);
        }

        $createdItems = $sale->items()->get();
        $this->syncSaleItemStocksAction->handle($createdItems, $branchId);
        $subTotal = (float) $createdItems->sum(fn (SaleItem $item): float => (float) $item->qty * (float) $item->unit_price);
        $discountTotal = (float) $createdItems->sum(fn (SaleItem $item): float => (float) $item->discount_amount);
        $taxTotal = (float) $createdItems->sum(fn (SaleItem $item): float => (float) $item->tax_amount);
        $grandTotal = (float) $createdItems->sum(fn (SaleItem $item): float => (float) $item->line_total);

        $sale->update([
            'sub_total' => $subTotal,
            'discount_total' => $discountTotal,
            'tax_total' => $taxTotal,
            'grand_total' => $grandTotal,
            'balance_due' => $grandTotal - (float) $sale->paid_total,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Product;

use App\Enums\StockMoveType;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\StockMove;

final class SyncProductOpeningStockAction
{
    public function handle(Product $product, string $branchId, float $targetStock, bool $isNewProduct = false): void
    {
        if (! $product->track_stock) {
            return;
        }

        $stockRows = InventoryStock::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $product->id)
            ->oldest('updated_at')
            ->get();

        $currentTotal = (float) $stockRows->sum(fn (InventoryStock $stock): float => (float) $stock->qty_on_hand);
        $normalizedTarget = max(0, $targetStock);

        if ($stockRows->isEmpty()) {
            $stockRow = InventoryStock::query()->create([
                'branch_id' => $branchId,
                'product_id' => $product->id,
                'qty_on_hand' => $normalizedTarget,
                'qty_reserved' => 0,
                'avg_cost' => 0,
            ]);
        } else {
            /** @var InventoryStock $stockRow */
            $stockRow = $stockRows->first();
            $stockRow->update([
                'qty_on_hand' => $normalizedTarget,
            ]);
            $stockRows
                ->slice(1)
                ->each(fn (InventoryStock $stock): bool => $stock->update(['qty_on_hand' => 0]));
        }

        $delta = $normalizedTarget - $currentTotal;
        if ($delta === 0.0) {
            return;
        }

        $moveType = $isNewProduct
            ? StockMoveType::OPENING
            : ($delta > 0 ? StockMoveType::ADJUSTMENT_IN : StockMoveType::ADJUSTMENT_OUT);

        StockMove::query()->create([
            'product_id' => $product->id,
            'branch_id' => $branchId,
            'warehouse_id' => null,
            'created_by' => auth('user')->id(),
            'move_type' => $moveType->value,
            'qty' => abs($delta),
            'unit_cost' => 0,
            'total_cost' => 0,
            'reference_type' => null,
            'reference_id' => null,
            'remarks' => $isNewProduct ? 'Opening stock from product create form.' : 'Stock adjusted from product edit form.',
            'occurred_at' => now(),
        ]);
    }
}

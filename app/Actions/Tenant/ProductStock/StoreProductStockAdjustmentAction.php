<?php

declare(strict_types=1);

namespace App\Actions\Tenant\ProductStock;

use App\Enums\StockMoveType;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\StockMove;
use App\Support\AuditTimelineLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

final class StoreProductStockAdjustmentAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, string $branchId): void
    {
        $productId = (string) $payload['product_id'];
        $action = (string) $payload['action'];
        $qty = (float) $payload['qty'];
        $remarks = isset($payload['remarks']) ? (string) $payload['remarks'] : null;
        $adjustmentSummary = [
            'previous_qty' => 0.0,
            'new_qty' => 0.0,
            'move_qty' => 0.0,
            'move_type' => null,
        ];

        InventoryStock::query()->getConnection()->transaction(function () use ($action, $branchId, $productId, $qty, $remarks, &$adjustmentSummary): void {
            $stockRow = InventoryStock::query()
                ->where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->first();

            if (! $stockRow instanceof InventoryStock) {
                $stockRow = InventoryStock::query()->create([
                    'branch_id' => $branchId,
                    'product_id' => $productId,
                    'qty_on_hand' => 0,
                    'qty_reserved' => 0,
                    'avg_cost' => 0,
                ]);
            }

            $currentQty = (float) $stockRow->qty_on_hand;
            $newQty = $currentQty;
            $moveType = StockMoveType::ADJUSTMENT_IN;
            $moveQty = 0.0;

            if ($action === 'in') {
                $newQty = $currentQty + $qty;
                $moveType = StockMoveType::ADJUSTMENT_IN;
                $moveQty = $qty;
            } elseif ($action === 'out') {
                $newQty = $currentQty - $qty;
                if ($newQty < 0) {
                    throw ValidationException::withMessages([
                        'qty' => 'Stock out quantity exceeds available stock.',
                    ]);
                }

                $moveType = StockMoveType::ADJUSTMENT_OUT;
                $moveQty = $qty;
            } else {
                $newQty = $qty;
                $delta = $newQty - $currentQty;
                if ($delta > 0) {
                    $moveType = StockMoveType::ADJUSTMENT_IN;
                } elseif ($delta < 0) {
                    $moveType = StockMoveType::ADJUSTMENT_OUT;
                }

                $moveQty = abs($delta);
            }

            $stockRow->update([
                'qty_on_hand' => $newQty,
            ]);

            if ($moveQty > 0) {
                StockMove::query()->create([
                    'product_id' => $productId,
                    'branch_id' => $branchId,
                    'warehouse_id' => null,
                    'created_by' => auth('user')->id(),
                    'move_type' => $moveType->value,
                    'qty' => $moveQty,
                    'unit_cost' => 0,
                    'total_cost' => 0,
                    'reference_type' => null,
                    'reference_id' => null,
                    'remarks' => $remarks,
                    'occurred_at' => now(),
                ]);
            }

            $adjustmentSummary = [
                'previous_qty' => $currentQty,
                'new_qty' => $newQty,
                'move_qty' => $moveQty,
                'move_type' => $moveType->value,
            ];
        });

        $product = Product::query()->find($productId);
        AuditTimelineLogger::log(
            event: 'inventory_stock_adjusted',
            description: 'Inventory stock adjusted.',
            causer: Auth::guard('user')->user(),
            subject: $product,
            properties: [
                'product_id' => $productId,
                'product_name' => (string) ($product?->name ?? ''),
                'action' => $action,
                'requested_qty' => $qty,
                'move_qty' => $adjustmentSummary['move_qty'],
                'move_type' => $adjustmentSummary['move_type'],
                'previous_qty' => $adjustmentSummary['previous_qty'],
                'new_qty' => $adjustmentSummary['new_qty'],
                'remarks' => $remarks,
            ],
        );
    }
}

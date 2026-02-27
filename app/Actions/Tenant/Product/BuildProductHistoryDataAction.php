<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Product;

use App\Enums\SaleStatus;
use App\Enums\StockMoveType;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\StockMove;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class BuildProductHistoryDataAction
{
    /**
     * @return array<string, mixed>
     */
    public function handle(Request $request, string $branchId): array
    {
        $selectedProductId = mb_trim($request->string('product_id')->toString());

        $products = Product::query()
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        $selectedProduct = null;
        $summary = null;
        $recentSales = collect();
        $recentStockMoves = collect();

        if ($selectedProductId !== '') {
            $selectedProduct = Product::query()->find($selectedProductId);
        }

        if ($selectedProduct instanceof Product) {
            $stockSnapshot = InventoryStock::query()
                ->where('branch_id', $branchId)
                ->where('product_id', $selectedProduct->id)
                ->first();

            $salesQuery = SaleItem::query()
                ->where('branch_id', $branchId)
                ->where('product_id', $selectedProduct->id)
                ->whereHas('sale', function (Builder $query): void {
                    $query->whereIn('status', [
                        SaleStatus::POSTED->value,
                        SaleStatus::RETURNED->value,
                    ]);
                });

            $stockMovesQuery = StockMove::query()
                ->where('branch_id', $branchId)
                ->where('product_id', $selectedProduct->id);

            $summary = [
                'sales_count' => (clone $salesQuery)->distinct('sale_id')->count('sale_id'),
                'sold_qty' => (float) ((clone $salesQuery)->sum('qty')),
                'sales_amount' => (float) ((clone $salesQuery)->sum('line_total')),
                'purchased_qty' => $this->sumMoveQty($stockMovesQuery, StockMoveType::PURCHASE),
                'purchase_return_qty' => $this->sumMoveQty($stockMovesQuery, StockMoveType::PURCHASE_RETURN),
                'adjusted_in_qty' => $this->sumMoveQty($stockMovesQuery, StockMoveType::ADJUSTMENT_IN),
                'adjusted_out_qty' => $this->sumMoveQty($stockMovesQuery, StockMoveType::ADJUSTMENT_OUT),
                'opening_qty' => $this->sumMoveQty($stockMovesQuery, StockMoveType::OPENING),
                'qty_on_hand' => (float) ($stockSnapshot?->qty_on_hand ?? 0),
                'qty_reserved' => (float) ($stockSnapshot?->qty_reserved ?? 0),
            ];

            $recentSales = (clone $salesQuery)
                ->with('sale')
                ->latest('created_at')
                ->limit(20)
                ->get();

            $recentStockMoves = (clone $stockMovesQuery)
                ->with('creator')
                ->latest('occurred_at')
                ->limit(20)
                ->get();
        }

        return [
            'products' => $products,
            'selectedProductId' => $selectedProductId,
            'selectedProduct' => $selectedProduct,
            'summary' => $summary,
            'recentSales' => $recentSales,
            'recentStockMoves' => $recentStockMoves,
        ];
    }

    /**
     * @param  Builder<StockMove>  $stockMovesQuery
     */
    private function sumMoveQty(Builder $stockMovesQuery, StockMoveType $moveType): float
    {
        return (float) ((clone $stockMovesQuery)
            ->where('move_type', $moveType->value)
            ->sum('qty'));
    }
}

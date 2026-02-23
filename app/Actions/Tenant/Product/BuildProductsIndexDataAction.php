<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Product;

use App\Enums\StockMoveType;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\StockMove;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class BuildProductsIndexDataAction
{
    public function handle(Request $request, string $branchId): LengthAwarePaginator
    {
        $perPage = min(max($request->integer('per_page', 15), 5), 100);
        $search = mb_trim($request->string('search')->toString());

        $products = Product::query()
            ->with(['category', 'brand', 'defaultUnit', 'defaultTax'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('sku', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('part_number', 'like', sprintf('%%%s%%', $search));
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $productIds = $products->getCollection()->pluck('id')->all();

        $stockByProduct = InventoryStock::query()
            ->where('branch_id', $branchId)
            ->whereIn('product_id', $productIds)
            ->selectRaw('product_id, SUM(qty_on_hand) as qty_on_hand')
            ->groupBy('product_id')
            ->pluck('qty_on_hand', 'product_id');

        $openingByProduct = StockMove::query()
            ->where('branch_id', $branchId)
            ->whereIn('product_id', $productIds)
            ->where('move_type', StockMoveType::OPENING->value)
            ->selectRaw('product_id, SUM(qty) as opening_qty')
            ->groupBy('product_id')
            ->pluck('opening_qty', 'product_id');

        $firstMoveByProduct = StockMove::query()
            ->where('branch_id', $branchId)
            ->whereIn('product_id', $productIds)
            ->oldest('occurred_at')->oldest()
            ->get(['product_id', 'qty'])
            ->groupBy('product_id')
            ->map(fn (Collection $moves): float => (float) $moves->first()?->qty ?: 0.0);

        $products->setCollection(
            $products->getCollection()->map(function (Product $product) use ($firstMoveByProduct, $openingByProduct, $stockByProduct): Product {
                $qtyOnHand = (float) ($stockByProduct[$product->id] ?? 0.0);
                $openingFromMove = (float) ($openingByProduct[$product->id] ?? 0.0);
                $openingFromFirstMove = (float) ($firstMoveByProduct[$product->id] ?? 0.0);
                $openingStock = $openingFromMove > 0 ? $openingFromMove : ($openingFromFirstMove > 0 ? $openingFromFirstMove : $qtyOnHand);
                $product->setAttribute('qty_on_hand', $qtyOnHand);
                $product->setAttribute('opening_stock', $openingStock);

                return $product;
            })
        );

        return $products;
    }
}

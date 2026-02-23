<?php

declare(strict_types=1);

namespace App\Actions\Tenant\ProductStock;

use App\Enums\RecordStatus;
use App\Models\Category;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Http\Request;

final class BuildInventoryTreeDataAction
{
    /**
     * @return array<string, mixed>
     */
    public function handle(Request $request, string $branchId): array
    {
        $search = mb_trim($request->string('search')->toString());
        $showInactive = $request->boolean('show_inactive');

        $products = Product::query()
            ->with(['category:id,name', 'brand:id,name', 'defaultUnit:id,name'])
            ->unless($showInactive, fn ($query) => $query->where('status', RecordStatus::ACTIVE->value))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder
                        ->where('name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('sku', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('part_number', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('barcode', 'like', sprintf('%%%s%%', $search));
                });
            })
            ->orderBy('name')
            ->get();

        $productIds = $products->pluck('id')->all();

        $pricesByProduct = ProductPrice::query()
            ->where('branch_id', $branchId)
            ->whereIn('product_id', $productIds)
            ->orderByDesc('effective_from')
            ->get()
            ->groupBy('product_id');

        $stocksByProduct = InventoryStock::query()
            ->where('branch_id', $branchId)
            ->whereIn('product_id', $productIds)
            ->get()
            ->groupBy('product_id');

        $categoryIds = $products
            ->pluck('category_id')
            ->filter(fn ($value): bool => is_string($value) && $value !== '')
            ->unique()
            ->values()
            ->all();

        $categoriesById = Category::query()
            ->whereIn('id', $categoryIds)
            ->orderBy('name')
            ->get()
            ->keyBy('id');

        $inventoryTree = collect();
        $groupedByCategory = $products->groupBy(fn (Product $product): string => (string) ($product->category_id ?: 'uncategorized'));

        foreach ($groupedByCategory as $categoryKey => $categoryProducts) {
            $categoryName = $categoryKey === 'uncategorized'
                ? 'Uncategorized'
                : (string) ($categoriesById->get($categoryKey)?->name ?? 'Unknown Category');

            $productRows = collect();
            foreach ($categoryProducts->sortBy('name')->values() as $product) {
                if (! $product instanceof Product) {
                    continue;
                }

                $latestPrice = $this->resolveLatestPrice($pricesByProduct->get($product->id));
                $stockRows = $stocksByProduct->get($product->id, collect())->values();
                $qtyOnHand = (float) $stockRows->sum(fn (InventoryStock $stock): float => (float) $stock->qty_on_hand);
                $qtyReserved = (float) $stockRows->sum(fn (InventoryStock $stock): float => (float) $stock->qty_reserved);

                $productRows->push([
                    'product' => $product,
                    'latest_price' => $latestPrice,
                    'stock_rows' => $stockRows,
                    'qty_on_hand' => $qtyOnHand,
                    'qty_reserved' => $qtyReserved,
                    'qty_available' => $qtyOnHand - $qtyReserved,
                ]);
            }

            $inventoryTree->push([
                'category_key' => $categoryKey,
                'category_name' => $categoryName,
                'products_count' => $productRows->count(),
                'products' => $productRows,
            ]);
        }

        $inventoryTree = $inventoryTree
            ->sortBy('category_name')
            ->values();

        $summary = [
            'categories_count' => $inventoryTree->count(),
            'products_count' => $products->count(),
            'qty_on_hand_total' => (float) $stocksByProduct->flatten()->sum(fn (InventoryStock $stock): float => (float) $stock->qty_on_hand),
            'qty_reserved_total' => (float) $stocksByProduct->flatten()->sum(fn (InventoryStock $stock): float => (float) $stock->qty_reserved),
        ];
        $summary['qty_available_total'] = $summary['qty_on_hand_total'] - $summary['qty_reserved_total'];

        return [
            'search' => $search,
            'showInactive' => $showInactive,
            'inventoryTree' => $inventoryTree,
            'summary' => $summary,
        ];
    }

    private function resolveLatestPrice($prices): ?ProductPrice
    {
        $latestPrice = $prices?->first();

        return $latestPrice instanceof ProductPrice ? $latestPrice : null;
    }
}

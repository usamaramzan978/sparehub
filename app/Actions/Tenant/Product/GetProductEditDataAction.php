<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Product;

use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\ProductPrice;

final readonly class GetProductEditDataAction
{
    public function __construct(private GetProductFormOptionsAction $getProductFormOptionsAction) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(Product $product, string $branchId): array
    {
        $data = $this->getProductFormOptionsAction->handle();

        $data['product'] = $product;
        $data['stockOnHand'] = (float) InventoryStock::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $product->id)
            ->sum('qty_on_hand');
        $data['latestPrice'] = ProductPrice::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $product->id)
            ->latest('effective_from')
            ->latest('created_at')
            ->first();

        return $data;
    }
}

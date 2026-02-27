<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Product;

use App\Models\Product;
use App\Models\ProductPrice;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

final class SyncProductPriceAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(Product $product, array $payload, string $branchId): ProductPrice
    {
        $pricePayload = [
            'product_id' => $product->id,
            'branch_id' => $branchId,
            'cost' => (float) ($payload['cost'] ?? 0),
            'mrp' => (float) ($payload['mrp'] ?? 0),
            'retail_price' => (float) ($payload['retail_price'] ?? 0),
            'wholesale_price' => (float) ($payload['wholesale_price'] ?? 0),
            'effective_from' => $this->resolveEffectiveFrom($payload['effective_from'] ?? null),
        ];

        $latestPrice = ProductPrice::query()
            ->where('product_id', $product->id)
            ->where('branch_id', $branchId)
            ->latest('effective_from')
            ->latest('created_at')
            ->first();

        if ($latestPrice instanceof ProductPrice) {
            $latestPrice->update($pricePayload);

            return $latestPrice->refresh();
        }

        return ProductPrice::query()->create($pricePayload);
    }

    private function resolveEffectiveFrom(mixed $effectiveFrom): CarbonInterface
    {
        if (is_string($effectiveFrom) && $effectiveFrom !== '') {
            return Carbon::parse($effectiveFrom);
        }

        return now();
    }
}

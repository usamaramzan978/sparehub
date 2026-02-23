<?php

declare(strict_types=1);

namespace App\Actions\Tenant\SaleHold;

use App\Models\SaleHold;

final class UpdateSaleHoldAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(SaleHold $saleHold, array $payload, string $branchId): bool
    {
        $payload['branch_id'] = $branchId;
        $payload['payload'] = json_decode((string) $payload['payload'], true, 512, JSON_THROW_ON_ERROR);

        return $saleHold->update($payload);
    }
}

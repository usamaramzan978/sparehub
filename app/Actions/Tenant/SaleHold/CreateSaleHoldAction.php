<?php

declare(strict_types=1);

namespace App\Actions\Tenant\SaleHold;

use App\Models\SaleHold;

final class CreateSaleHoldAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, string $branchId, mixed $createdBy): SaleHold
    {
        $payload['branch_id'] = $branchId;
        $payload['created_by'] = $createdBy;
        $payload['payload'] = json_decode((string) $payload['payload'], true, 512, JSON_THROW_ON_ERROR);

        return SaleHold::query()->create($payload);
    }
}

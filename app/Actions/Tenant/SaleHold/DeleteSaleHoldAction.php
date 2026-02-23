<?php

declare(strict_types=1);

namespace App\Actions\Tenant\SaleHold;

use App\Models\SaleHold;

final class DeleteSaleHoldAction
{
    public function handle(SaleHold $saleHold): bool
    {
        return (bool) $saleHold->delete();
    }
}

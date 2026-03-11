<?php

declare(strict_types=1);

namespace App\Actions\Tenant\SaleReturn;

use App\Models\SaleReturn;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class EnsureSaleReturnInBranchAction
{
    public function handle(SaleReturn $saleReturn, string $branchId): SaleReturn
    {
        throw_if($saleReturn->branch_id !== $branchId, NotFoundHttpException::class);

        return $saleReturn;
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Tenant\SaleReturn;

use App\Models\SaleReturn;
use App\Support\AuditTimelineLogger;
use Illuminate\Support\Facades\Auth;

final readonly class DeleteSaleReturnAction
{
    public function __construct(private SyncSaleReturnItemStocksAction $syncSaleReturnItemStocksAction) {}

    public function handle(SaleReturn $saleReturn): bool
    {
        $items = $saleReturn->items()->get();

        $deleted = SaleReturn::query()->getConnection()->transaction(function () use ($saleReturn, $items): bool {
            $this->syncSaleReturnItemStocksAction->handle($items, $saleReturn->branch_id, reverse: true);
            $saleReturn->items()->delete();

            return (bool) $saleReturn->delete();
        });

        if ($deleted) {
            AuditTimelineLogger::log(
                event: 'sale_return_deleted',
                description: 'Sale return deleted.',
                causer: Auth::guard('user')->user(),
                subject: $saleReturn,
                properties: [
                    'sale_return_id' => (string) $saleReturn->id,
                    'return_no' => $saleReturn->return_no,
                ],
            );
        }

        return $deleted;
    }
}

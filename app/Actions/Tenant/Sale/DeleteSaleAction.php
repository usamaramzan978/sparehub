<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Sale;

use App\Models\Sale;
use App\Support\AuditTimelineLogger;
use Illuminate\Support\Facades\Auth;

final readonly class DeleteSaleAction
{
    public function __construct(private SyncSaleItemStocksAction $syncSaleItemStocksAction) {}

    public function handle(Sale $sale): bool
    {
        $snapshot = [
            'sale_id' => (string) $sale->id,
            'invoice_no' => $sale->invoice_no,
            'grand_total' => (float) $sale->grand_total,
        ];

        $this->syncSaleItemStocksAction->handle($sale->items()->get(), $sale->branch_id, reverse: true);
        $deleted = (bool) $sale->delete();

        AuditTimelineLogger::log(
            event: 'sale_deleted',
            description: 'Sale invoice deleted.',
            causer: Auth::guard('user')->user(),
            subject: $sale,
            properties: $snapshot,
        );

        return $deleted;
    }
}

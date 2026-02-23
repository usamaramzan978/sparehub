<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Purchase;

use App\Models\Purchase;
use App\Support\AuditTimelineLogger;
use Illuminate\Support\Facades\Auth;

final readonly class DeletePurchaseAction
{
    public function __construct(private SyncPurchaseItemStocksAction $syncPurchaseItemStocksAction) {}

    public function handle(Purchase $purchase): bool
    {
        $snapshot = [
            'purchase_id' => (string) $purchase->id,
            'purchase_no' => $purchase->purchase_no,
            'grand_total' => (float) $purchase->grand_total,
        ];

        $this->syncPurchaseItemStocksAction->handle($purchase->items()->get(), $purchase->branch_id, reverse: true);
        $deleted = (bool) $purchase->delete();

        AuditTimelineLogger::log(
            event: 'purchase_deleted',
            description: 'Purchase invoice deleted.',
            causer: Auth::guard('user')->user(),
            subject: $purchase,
            properties: $snapshot,
        );

        return $deleted;
    }
}

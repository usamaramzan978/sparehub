<?php

declare(strict_types=1);

namespace App\Actions\Tenant\PurchaseReturn;

use App\Models\PurchaseReturn;
use App\Support\AuditTimelineLogger;
use Illuminate\Support\Facades\Auth;

final readonly class DeletePurchaseReturnAction
{
    public function __construct(private SyncPurchaseReturnItemStocksAction $syncPurchaseReturnItemStocksAction) {}

    public function handle(PurchaseReturn $purchaseReturn): bool
    {
        $snapshot = [
            'purchase_return_id' => (string) $purchaseReturn->id,
            'return_no' => $purchaseReturn->return_no,
            'grand_total' => (float) $purchaseReturn->grand_total,
        ];

        $this->syncPurchaseReturnItemStocksAction->handle($purchaseReturn->items()->get(), $purchaseReturn->branch_id, reverse: true);
        $deleted = (bool) $purchaseReturn->delete();

        AuditTimelineLogger::log(
            event: 'purchase_return_deleted',
            description: 'Purchase return deleted.',
            causer: Auth::guard('user')->user(),
            subject: $purchaseReturn,
            properties: $snapshot,
        );

        return $deleted;
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Tenant\PurchaseReturn;

use App\Models\PurchaseReturn;
use App\Support\AuditTimelineLogger;
use Illuminate\Support\Facades\Auth;

final readonly class UpdatePurchaseReturnAction
{
    public function __construct(private SyncPurchaseReturnItemsAction $syncPurchaseReturnItemsAction) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(PurchaseReturn $purchaseReturn, array $payload, string $branchId): void
    {
        $items = $payload['items'];
        unset($payload['items']);

        $payload['branch_id'] = $branchId;
        $payload['return_no'] = $purchaseReturn->return_no;

        PurchaseReturn::query()->getConnection()->transaction(function () use ($purchaseReturn, $payload, $items): void {
            $purchaseReturn->update($payload);
            $this->syncPurchaseReturnItemsAction->handle($purchaseReturn, $items);
        });

        AuditTimelineLogger::log(
            event: 'purchase_return_updated',
            description: 'Purchase return updated.',
            causer: Auth::guard('user')->user(),
            subject: $purchaseReturn,
            properties: [
                'purchase_return_id' => (string) $purchaseReturn->id,
                'return_no' => $purchaseReturn->return_no,
                'changed_attributes' => array_keys($payload),
            ],
        );
    }
}

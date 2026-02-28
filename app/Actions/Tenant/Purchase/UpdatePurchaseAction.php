<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Purchase;

use App\Models\Purchase;
use App\Support\AuditTimelineLogger;
use Illuminate\Support\Facades\Auth;

final readonly class UpdatePurchaseAction
{
    public function __construct(private SyncPurchaseItemsAction $syncPurchaseItemsAction) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(Purchase $purchase, array $payload, string $branchId): void
    {
        $items = $payload['items'];
        unset($payload['items']);

        $payload['branch_id'] = $branchId;
        $payload['purchase_no'] = $purchase->purchase_no;

        Purchase::query()->getConnection()->transaction(function () use ($purchase, $payload, $items, $branchId): void {
            $purchase->update($payload);
            $this->syncPurchaseItemsAction->handle($purchase, $items, $branchId);
        });

        AuditTimelineLogger::log(
            event: 'purchase_updated',
            description: 'Purchase invoice updated.',
            causer: Auth::guard('user')->user(),
            subject: $purchase,
            properties: [
                'purchase_id' => (string) $purchase->id,
                'purchase_no' => $purchase->purchase_no,
                'changed_attributes' => array_keys($payload),
            ],
        );
    }
}

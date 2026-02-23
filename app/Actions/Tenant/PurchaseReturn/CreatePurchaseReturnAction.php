<?php

declare(strict_types=1);

namespace App\Actions\Tenant\PurchaseReturn;

use App\Models\PurchaseReturn;
use App\Support\AuditTimelineLogger;
use Illuminate\Support\Facades\Auth;

final readonly class CreatePurchaseReturnAction
{
    public function __construct(private SyncPurchaseReturnItemsAction $syncPurchaseReturnItemsAction) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, string $branchId, mixed $createdBy): PurchaseReturn
    {
        $items = $payload['items'];
        unset($payload['items']);

        $payload['branch_id'] = $branchId;
        $payload['created_by'] = $createdBy;

        $createdReturn = PurchaseReturn::query()->getConnection()->transaction(function () use ($payload, $items): PurchaseReturn {
            $purchaseReturn = PurchaseReturn::query()->create($payload);
            $this->syncPurchaseReturnItemsAction->handle($purchaseReturn, $items);

            return $purchaseReturn;
        });

        AuditTimelineLogger::log(
            event: 'purchase_return_created',
            description: 'Purchase return created.',
            causer: Auth::guard('user')->user(),
            subject: $createdReturn,
            properties: [
                'purchase_return_id' => (string) $createdReturn->id,
                'return_no' => $createdReturn->return_no,
                'grand_total' => (float) $createdReturn->grand_total,
            ],
        );

        return $createdReturn;
    }
}

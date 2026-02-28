<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Purchase;

use App\Models\Purchase;
use App\Support\AuditTimelineLogger;
use Illuminate\Support\Facades\Auth;

final readonly class CreatePurchaseAction
{
    public function __construct(
        private SyncPurchaseItemsAction $syncPurchaseItemsAction,
        private GeneratePurchaseNumberAction $generatePurchaseNumberAction
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, string $branchId, mixed $createdBy): Purchase
    {
        $items = $payload['items'];
        unset($payload['items']);

        $payload['branch_id'] = $branchId;
        $payload['created_by'] = $createdBy;
        $payload['purchase_no'] = $this->resolvePurchaseNumber($payload, $branchId);

        $createdPurchase = Purchase::query()->getConnection()->transaction(function () use ($payload, $items, $branchId): Purchase {
            $purchase = Purchase::query()->create($payload);
            $this->syncPurchaseItemsAction->handle($purchase, $items, $branchId);

            return $purchase;
        });

        AuditTimelineLogger::log(
            event: 'purchase_created',
            description: 'Purchase invoice created.',
            causer: Auth::guard('user')->user(),
            subject: $createdPurchase,
            properties: [
                'purchase_id' => (string) $createdPurchase->id,
                'purchase_no' => $createdPurchase->purchase_no,
                'grand_total' => (float) $createdPurchase->grand_total,
                'status' => $createdPurchase->status->value,
            ],
        );

        return $createdPurchase;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolvePurchaseNumber(array $payload, string $branchId): string
    {
        $purchaseNumber = isset($payload['purchase_no']) && is_string($payload['purchase_no'])
            ? mb_trim($payload['purchase_no'])
            : '';

        if ($purchaseNumber !== '') {
            return $purchaseNumber;
        }

        return $this->generatePurchaseNumberAction->handle($branchId);
    }
}

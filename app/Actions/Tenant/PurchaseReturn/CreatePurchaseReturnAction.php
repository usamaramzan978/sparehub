<?php

declare(strict_types=1);

namespace App\Actions\Tenant\PurchaseReturn;

use App\Models\PurchaseReturn;
use App\Support\AuditTimelineLogger;
use Illuminate\Support\Facades\Auth;

final readonly class CreatePurchaseReturnAction
{
    public function __construct(
        private SyncPurchaseReturnItemsAction $syncPurchaseReturnItemsAction,
        private GeneratePurchaseReturnNumberAction $generatePurchaseReturnNumberAction
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, string $branchId, mixed $createdBy): PurchaseReturn
    {
        $items = $payload['items'];
        unset($payload['items']);

        $payload['branch_id'] = $branchId;
        $payload['created_by'] = $createdBy;
        $payload['return_no'] = $this->resolveReturnNumber($payload, $branchId);

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

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveReturnNumber(array $payload, string $branchId): string
    {
        $returnNumber = isset($payload['return_no']) && is_string($payload['return_no'])
            ? mb_trim($payload['return_no'])
            : '';

        if ($returnNumber !== '') {
            return $returnNumber;
        }

        return $this->generatePurchaseReturnNumberAction->handle($branchId);
    }
}

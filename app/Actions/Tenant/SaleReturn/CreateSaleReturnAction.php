<?php

declare(strict_types=1);

namespace App\Actions\Tenant\SaleReturn;

use App\Models\SaleReturn;
use App\Support\AuditTimelineLogger;
use Illuminate\Support\Facades\Auth;

final readonly class CreateSaleReturnAction
{
    public function __construct(private SyncSaleReturnItemsAction $syncSaleReturnItemsAction) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, string $branchId, mixed $createdBy): SaleReturn
    {
        $items = $payload['items'];
        unset($payload['items']);

        $payload['branch_id'] = $branchId;
        $payload['created_by'] = $createdBy;

        $createdReturn = SaleReturn::query()->getConnection()->transaction(function () use ($payload, $items): SaleReturn {
            $saleReturn = SaleReturn::query()->create($payload);
            $this->syncSaleReturnItemsAction->handle($saleReturn, $items);

            return $saleReturn;
        });

        AuditTimelineLogger::log(
            event: 'sale_return_created',
            description: 'Sale return created.',
            causer: Auth::guard('user')->user(),
            subject: $createdReturn,
            properties: [
                'sale_return_id' => (string) $createdReturn->id,
                'return_no' => $createdReturn->return_no,
                'grand_total' => (float) $createdReturn->grand_total,
            ],
        );

        return $createdReturn;
    }
}

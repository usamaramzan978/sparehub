<?php

declare(strict_types=1);

namespace App\Actions\Tenant\SaleReturn;

use App\Models\SaleReturn;
use App\Support\AuditTimelineLogger;
use Illuminate\Support\Facades\Auth;

final readonly class UpdateSaleReturnAction
{
    public function __construct(private SyncSaleReturnItemsAction $syncSaleReturnItemsAction) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(SaleReturn $saleReturn, array $payload, string $branchId): SaleReturn
    {
        $items = $payload['items'];
        unset($payload['items']);

        $payload['branch_id'] = $branchId;

        $updatedReturn = SaleReturn::query()->getConnection()->transaction(function () use ($saleReturn, $payload, $items): SaleReturn {
            $saleReturn->update($payload);
            $this->syncSaleReturnItemsAction->handle($saleReturn, $items);

            return $saleReturn->refresh();
        });

        AuditTimelineLogger::log(
            event: 'sale_return_updated',
            description: 'Sale return updated.',
            causer: Auth::guard('user')->user(),
            subject: $updatedReturn,
            properties: [
                'sale_return_id' => (string) $updatedReturn->id,
                'return_no' => $updatedReturn->return_no,
                'grand_total' => (float) $updatedReturn->grand_total,
            ],
        );

        return $updatedReturn;
    }
}

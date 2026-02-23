<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Sale;

use App\Models\Sale;
use App\Support\AuditTimelineLogger;
use Illuminate\Support\Facades\Auth;

final readonly class UpdateSaleAction
{
    public function __construct(private SyncSaleItemsAction $syncSaleItemsAction) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(Sale $sale, array $payload, string $branchId): void
    {
        $items = $payload['items'];
        unset($payload['items']);

        $payload['branch_id'] = $branchId;

        Sale::query()->getConnection()->transaction(function () use ($sale, $payload, $items, $branchId): void {
            $sale->update($payload);
            $this->syncSaleItemsAction->handle($sale, $items, $branchId);
        });

        AuditTimelineLogger::log(
            event: 'sale_updated',
            description: 'Sale invoice updated.',
            causer: Auth::guard('user')->user(),
            subject: $sale,
            properties: [
                'sale_id' => (string) $sale->id,
                'invoice_no' => $sale->invoice_no,
                'changed_attributes' => array_keys($payload),
            ],
        );
    }
}

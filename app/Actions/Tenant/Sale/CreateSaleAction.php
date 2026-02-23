<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Sale;

use App\Models\Sale;
use App\Support\AuditTimelineLogger;
use Illuminate\Support\Facades\Auth;

final readonly class CreateSaleAction
{
    public function __construct(private SyncSaleItemsAction $syncSaleItemsAction) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, string $branchId, mixed $createdBy): Sale
    {
        $items = $payload['items'];
        unset($payload['items']);

        $payload['branch_id'] = $branchId;
        $payload['created_by'] = $createdBy;

        $createdSale = null;

        Sale::query()->getConnection()->transaction(function () use ($payload, $items, $branchId, &$createdSale): void {
            $sale = Sale::query()->create($payload);
            $this->syncSaleItemsAction->handle($sale, $items, $branchId);
            $createdSale = $sale;
        });

        if ($createdSale instanceof Sale) {
            AuditTimelineLogger::log(
                event: 'sale_created',
                description: 'Sale invoice created.',
                causer: Auth::guard('user')->user(),
                subject: $createdSale,
                properties: [
                    'sale_id' => (string) $createdSale->id,
                    'invoice_no' => $createdSale->invoice_no,
                    'grand_total' => (float) $createdSale->grand_total,
                    'status' => $createdSale->status->value,
                ],
            );
        }

        return $createdSale;
    }
}

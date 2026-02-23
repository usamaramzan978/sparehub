<?php

declare(strict_types=1);

namespace App\Actions\Tenant\SalePayment;

use App\Models\SalePayment;
use App\Support\AuditTimelineLogger;
use BackedEnum;

final readonly class UpdateSalePaymentAction
{
    public function __construct(private RecalculateSalePaidTotalsAction $recalculateSalePaidTotalsAction) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(SalePayment $salePayment, array $payload, string $branchId, mixed $causer): bool
    {
        $oldSale = $salePayment->sale;

        $payload['branch_id'] = $branchId;

        $updated = $salePayment->update($payload);

        $this->recalculateSalePaidTotalsAction->handle($oldSale);
        $this->recalculateSalePaidTotalsAction->handle($salePayment->sale);

        AuditTimelineLogger::log(
            event: 'sale_payment_updated',
            description: 'Sale payment updated.',
            causer: $causer,
            subject: $salePayment,
            properties: [
                'sale_id' => (string) $salePayment->sale_id,
                'payment_id' => (string) $salePayment->id,
                'amount' => (float) $salePayment->amount,
                'method' => $this->paymentMethodValue($salePayment->payment_method),
            ],
        );

        return $updated;
    }

    private function paymentMethodValue(mixed $method): string
    {
        if ($method instanceof BackedEnum) {
            return (string) $method->value;
        }

        return (string) $method;
    }
}

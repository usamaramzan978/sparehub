<?php

declare(strict_types=1);

namespace App\Actions\Tenant\SalePayment;

use App\Models\SalePayment;
use App\Support\AuditTimelineLogger;
use BackedEnum;

final readonly class DeleteSalePaymentAction
{
    public function __construct(private RecalculateSalePaidTotalsAction $recalculateSalePaidTotalsAction) {}

    public function handle(SalePayment $salePayment, mixed $causer): bool
    {
        $sale = $salePayment->sale;
        $snapshot = [
            'sale_id' => (string) $salePayment->sale_id,
            'payment_id' => (string) $salePayment->id,
            'amount' => (float) $salePayment->amount,
            'method' => $this->paymentMethodValue($salePayment->payment_method),
        ];

        $deleted = (bool) $salePayment->delete();
        $this->recalculateSalePaidTotalsAction->handle($sale);

        AuditTimelineLogger::log(
            event: 'sale_payment_deleted',
            description: 'Sale payment deleted.',
            causer: $causer,
            subject: $salePayment,
            properties: $snapshot,
        );

        return $deleted;
    }

    private function paymentMethodValue(mixed $method): string
    {
        if ($method instanceof BackedEnum) {
            return (string) $method->value;
        }

        return (string) $method;
    }
}

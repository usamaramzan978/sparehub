<?php

declare(strict_types=1);

namespace App\Actions\Tenant\SalePayment;

use App\Models\SalePayment;
use App\Support\AuditTimelineLogger;
use BackedEnum;

final readonly class CreateSalePaymentAction
{
    public function __construct(private RecalculateSalePaidTotalsAction $recalculateSalePaidTotalsAction) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, string $branchId, mixed $causer): SalePayment
    {
        $payload['branch_id'] = $branchId;

        $payment = SalePayment::query()->create($payload);
        $this->recalculateSalePaidTotalsAction->handle($payment->sale);

        AuditTimelineLogger::log(
            event: 'sale_payment_recorded',
            description: 'Sale payment recorded.',
            causer: $causer,
            subject: $payment,
            properties: [
                'sale_id' => (string) $payment->sale_id,
                'payment_id' => (string) $payment->id,
                'amount' => (float) $payment->amount,
                'method' => $this->paymentMethodValue($payment->payment_method),
            ],
        );

        return $payment;
    }

    private function paymentMethodValue(mixed $method): string
    {
        if ($method instanceof BackedEnum) {
            return (string) $method->value;
        }

        return (string) $method;
    }
}

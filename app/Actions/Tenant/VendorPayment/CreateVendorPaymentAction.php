<?php

declare(strict_types=1);

namespace App\Actions\Tenant\VendorPayment;

use App\Models\VendorPayment;
use App\Support\AuditTimelineLogger;
use BackedEnum;

final readonly class CreateVendorPaymentAction
{
    public function __construct(
        private RecalculatePurchasePaidTotalsAction $recalculatePurchasePaidTotalsAction,
        private GenerateVendorPaymentNumberAction $generateVendorPaymentNumberAction
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, string $branchId, mixed $createdBy, mixed $causer): VendorPayment
    {
        $payload['branch_id'] = $branchId;
        $payload['created_by'] = $createdBy;
        $payload['payment_no'] = $this->resolvePaymentNumber($payload, $branchId);

        $payment = VendorPayment::query()->create($payload);
        $this->recalculatePurchasePaidTotalsAction->handle($payment->purchase);

        AuditTimelineLogger::log(
            event: 'vendor_payment_recorded',
            description: 'Vendor payment recorded.',
            causer: $causer,
            subject: $payment,
            properties: [
                'purchase_id' => (string) ($payment->purchase_id ?? ''),
                'payment_id' => (string) $payment->id,
                'amount' => (float) $payment->amount,
                'method' => $this->paymentMethodValue($payment->payment_method),
            ],
        );

        return $payment;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolvePaymentNumber(array $payload, string $branchId): string
    {
        $paymentNumber = isset($payload['payment_no']) && is_string($payload['payment_no'])
            ? mb_trim($payload['payment_no'])
            : '';

        if ($paymentNumber !== '') {
            return $paymentNumber;
        }

        return $this->generateVendorPaymentNumberAction->handle($branchId);
    }

    private function paymentMethodValue(mixed $method): string
    {
        if ($method instanceof BackedEnum) {
            return (string) $method->value;
        }

        return (string) $method;
    }
}

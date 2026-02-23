<?php

declare(strict_types=1);

namespace App\Actions\Tenant\VendorPayment;

use App\Models\VendorPayment;
use App\Support\AuditTimelineLogger;
use BackedEnum;

final readonly class UpdateVendorPaymentAction
{
    public function __construct(private RecalculatePurchasePaidTotalsAction $recalculatePurchasePaidTotalsAction) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(VendorPayment $vendorPayment, array $payload, string $branchId, mixed $causer): bool
    {
        $oldPurchase = $vendorPayment->purchase;

        $payload['branch_id'] = $branchId;

        $updated = $vendorPayment->update($payload);

        $this->recalculatePurchasePaidTotalsAction->handle($oldPurchase);
        $this->recalculatePurchasePaidTotalsAction->handle($vendorPayment->purchase);

        AuditTimelineLogger::log(
            event: 'vendor_payment_updated',
            description: 'Vendor payment updated.',
            causer: $causer,
            subject: $vendorPayment,
            properties: [
                'purchase_id' => (string) ($vendorPayment->purchase_id ?? ''),
                'payment_id' => (string) $vendorPayment->id,
                'amount' => (float) $vendorPayment->amount,
                'method' => $this->paymentMethodValue($vendorPayment->payment_method),
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

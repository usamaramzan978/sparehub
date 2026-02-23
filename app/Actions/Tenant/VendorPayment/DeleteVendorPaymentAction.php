<?php

declare(strict_types=1);

namespace App\Actions\Tenant\VendorPayment;

use App\Models\VendorPayment;
use App\Support\AuditTimelineLogger;
use BackedEnum;

final readonly class DeleteVendorPaymentAction
{
    public function __construct(private RecalculatePurchasePaidTotalsAction $recalculatePurchasePaidTotalsAction) {}

    public function handle(VendorPayment $vendorPayment, mixed $causer): bool
    {
        $purchase = $vendorPayment->purchase;
        $snapshot = [
            'purchase_id' => (string) ($vendorPayment->purchase_id ?? ''),
            'payment_id' => (string) $vendorPayment->id,
            'amount' => (float) $vendorPayment->amount,
            'method' => $this->paymentMethodValue($vendorPayment->payment_method),
        ];

        $deleted = (bool) $vendorPayment->delete();
        $this->recalculatePurchasePaidTotalsAction->handle($purchase);

        AuditTimelineLogger::log(
            event: 'vendor_payment_deleted',
            description: 'Vendor payment deleted.',
            causer: $causer,
            subject: $vendorPayment,
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

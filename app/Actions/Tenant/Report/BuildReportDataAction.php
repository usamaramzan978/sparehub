<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Report;

use App\Enums\InvoiceType;
use App\Enums\PaymentMethodType;
use App\Enums\PurchaseStatus;
use App\Enums\SaleStatus;
use App\Models\Customer;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\Vendor;
use App\Models\VendorPayment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class BuildReportDataAction
{
    /**
     * @return array<string, mixed>
     */
    public function handle(Request $request, string $branchId, bool $paginate): array
    {
        $dateFrom = mb_trim($request->string('date_from')->toString());
        $dateTo = mb_trim($request->string('date_to')->toString());
        $saleStatus = mb_trim($request->string('sale_status')->toString());
        $purchaseStatus = mb_trim($request->string('purchase_status')->toString());
        $invoiceType = mb_trim($request->string('invoice_type')->toString());
        $paymentMethod = mb_trim($request->string('payment_method')->toString());
        $customerId = mb_trim($request->string('customer_id')->toString());
        $vendorId = mb_trim($request->string('vendor_id')->toString());
        $search = mb_trim($request->string('search')->toString());
        $minTotal = mb_trim($request->string('min_total')->toString());
        $maxTotal = mb_trim($request->string('max_total')->toString());

        $salesBase = Sale::query()
            ->with(['customer'])
            ->where('branch_id', $branchId)
            ->when($dateFrom !== '', fn (Builder $query) => $query->whereDate('invoice_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn (Builder $query) => $query->whereDate('invoice_date', '<=', $dateTo))
            ->when($saleStatus !== '', fn (Builder $query) => $query->where('status', $saleStatus))
            ->when($invoiceType !== '', fn (Builder $query) => $query->where('invoice_type', $invoiceType))
            ->when($customerId !== '', fn (Builder $query) => $query->where('customer_id', $customerId))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('invoice_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('customer', fn (Builder $customerQuery) => $customerQuery->where('name', 'like', sprintf('%%%s%%', $search)));
                });
            })
            ->when(is_numeric($minTotal), fn (Builder $query) => $query->where('grand_total', '>=', (float) $minTotal))
            ->when(is_numeric($maxTotal), fn (Builder $query) => $query->where('grand_total', '<=', (float) $maxTotal));

        $purchasesBase = Purchase::query()
            ->with(['vendor', 'warehouse'])
            ->where('branch_id', $branchId)
            ->when($dateFrom !== '', fn (Builder $query) => $query->whereDate('purchase_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn (Builder $query) => $query->whereDate('purchase_date', '<=', $dateTo))
            ->when($purchaseStatus !== '', fn (Builder $query) => $query->where('status', $purchaseStatus))
            ->when($vendorId !== '', fn (Builder $query) => $query->where('vendor_id', $vendorId))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('purchase_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('vendor_invoice_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('vendor', fn (Builder $vendorQuery) => $vendorQuery->where('name', 'like', sprintf('%%%s%%', $search)));
                });
            })
            ->when(is_numeric($minTotal), fn (Builder $query) => $query->where('grand_total', '>=', (float) $minTotal))
            ->when(is_numeric($maxTotal), fn (Builder $query) => $query->where('grand_total', '<=', (float) $maxTotal));

        $salePaymentsBase = SalePayment::query()
            ->with(['sale.customer', 'receiver'])
            ->where('branch_id', $branchId)
            ->when($dateFrom !== '', fn (Builder $query) => $query->whereDate('paid_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn (Builder $query) => $query->whereDate('paid_at', '<=', $dateTo))
            ->when($paymentMethod !== '', fn (Builder $query) => $query->where('payment_method', $paymentMethod))
            ->when($customerId !== '', fn (Builder $query) => $query->whereHas('sale', fn (Builder $saleQuery) => $saleQuery->where('customer_id', $customerId)))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('reference_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('sale', fn (Builder $saleQuery) => $saleQuery->where('invoice_no', 'like', sprintf('%%%s%%', $search)));
                });
            });

        $vendorPaymentsBase = VendorPayment::query()
            ->with(['vendor', 'purchase', 'creator'])
            ->where('branch_id', $branchId)
            ->when($dateFrom !== '', fn (Builder $query) => $query->whereDate('paid_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn (Builder $query) => $query->whereDate('paid_at', '<=', $dateTo))
            ->when($paymentMethod !== '', fn (Builder $query) => $query->where('payment_method', $paymentMethod))
            ->when($vendorId !== '', fn (Builder $query) => $query->where('vendor_id', $vendorId))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('payment_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('reference_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('vendor', fn (Builder $vendorQuery) => $vendorQuery->where('name', 'like', sprintf('%%%s%%', $search)));
                });
            });

        if ($paginate) {
            $sales = (clone $salesBase)->latest('invoice_date')->paginate(15, ['*'], 'sales_page')->withQueryString();
            $purchases = (clone $purchasesBase)->latest('purchase_date')->paginate(15, ['*'], 'purchases_page')->withQueryString();
            $salePayments = (clone $salePaymentsBase)->latest('paid_at')->paginate(15, ['*'], 'sale_payments_page')->withQueryString();
            $vendorPayments = (clone $vendorPaymentsBase)->latest('paid_at')->paginate(15, ['*'], 'vendor_payments_page')->withQueryString();
            $receivables = (clone $salesBase)->where('balance_due', '>', 0)->latest('invoice_date')->paginate(15, ['*'], 'receivables_page')->withQueryString();
            $payables = (clone $purchasesBase)->where('balance_due', '>', 0)->latest('purchase_date')->paginate(15, ['*'], 'payables_page')->withQueryString();
        } else {
            $sales = (clone $salesBase)->latest('invoice_date')->get();
            $purchases = (clone $purchasesBase)->latest('purchase_date')->get();
            $salePayments = (clone $salePaymentsBase)->latest('paid_at')->get();
            $vendorPayments = (clone $vendorPaymentsBase)->latest('paid_at')->get();
            $receivables = (clone $salesBase)->where('balance_due', '>', 0)->latest('invoice_date')->get();
            $payables = (clone $purchasesBase)->where('balance_due', '>', 0)->latest('purchase_date')->get();
        }

        $summary = [
            'sales_count' => (clone $salesBase)->count(),
            'sales_total' => (float) (clone $salesBase)->sum('grand_total'),
            'purchases_count' => (clone $purchasesBase)->count(),
            'purchases_total' => (float) (clone $purchasesBase)->sum('grand_total'),
            'sale_payments_total' => (float) (clone $salePaymentsBase)->sum('amount'),
            'vendor_payments_total' => (float) (clone $vendorPaymentsBase)->sum('amount'),
            'receivables_total' => (float) (clone $salesBase)->where('balance_due', '>', 0)->sum('balance_due'),
            'payables_total' => (float) (clone $purchasesBase)->where('balance_due', '>', 0)->sum('balance_due'),
        ];

        return [
            'sales' => $sales,
            'purchases' => $purchases,
            'salePayments' => $salePayments,
            'vendorPayments' => $vendorPayments,
            'receivables' => $receivables,
            'payables' => $payables,
            'summary' => $summary,
            'customers' => Customer::query()->where('branch_id', $branchId)->orderBy('name')->get(),
            'vendors' => Vendor::query()->where('branch_id', $branchId)->orderBy('name')->get(),
            'saleStatuses' => SaleStatus::cases(),
            'purchaseStatuses' => PurchaseStatus::cases(),
            'invoiceTypes' => InvoiceType::cases(),
            'paymentMethods' => PaymentMethodType::cases(),
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'sale_status' => $saleStatus,
                'purchase_status' => $purchaseStatus,
                'invoice_type' => $invoiceType,
                'payment_method' => $paymentMethod,
                'customer_id' => $customerId,
                'vendor_id' => $vendorId,
                'search' => $search,
                'min_total' => $minTotal,
                'max_total' => $maxTotal,
            ],
        ];
    }
}

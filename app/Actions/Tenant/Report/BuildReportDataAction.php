<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Report;

use App\Enums\InvoiceType;
use App\Enums\PaymentMethodType;
use App\Enums\PurchaseStatus;
use App\Enums\SaleStatus;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Vendor;
use App\Models\VendorPayment;
use App\Support\TenantDateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $dateFromAt = $dateFrom !== '' ? TenantDateTime::startOfDay($dateFrom) : null;
        $dateToAt = $dateTo !== '' ? TenantDateTime::endOfDay($dateTo) : null;
        $purchaseOutstandingExpression = 'purchases.grand_total - COALESCE((SELECT SUM(vendor_payments.amount) FROM vendor_payments WHERE vendor_payments.purchase_id = purchases.id), 0)';

        $salesBase = Sale::query()
            ->with(['customer'])
            ->where('branch_id', $branchId)
            ->when($dateFrom !== '', fn (Builder $query) => $query->whereDate('invoice_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn (Builder $query) => $query->whereDate('invoice_date', '<=', $dateTo))
            ->when($saleStatus !== '', fn (Builder $query) => $query->where('status', $saleStatus))
            ->when($invoiceType !== '', fn (Builder $query) => $query->where('invoice_type', $invoiceType))
            ->when($customerId !== '', fn (Builder $query) => $query->where('customer_id', $customerId))
            ->when(filled($search), function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('invoice_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('customer', fn (Builder $customerQuery) => $customerQuery->where('name', 'like', sprintf('%%%s%%', $search)));
                });
            })
            ->when(is_numeric($minTotal), fn (Builder $query) => $query->where('grand_total', '>=', (float) $minTotal))
            ->when(is_numeric($maxTotal), fn (Builder $query) => $query->where('grand_total', '<=', (float) $maxTotal));

        $purchasesBase = Purchase::query()
            ->with(['vendor'])
            ->select('purchases.*')
            ->selectRaw($purchaseOutstandingExpression.' as outstanding_balance')
            ->where('branch_id', $branchId)
            ->when($dateFrom !== '', fn (Builder $query) => $query->whereDate('purchase_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn (Builder $query) => $query->whereDate('purchase_date', '<=', $dateTo))
            ->when($purchaseStatus !== '', fn (Builder $query) => $query->where('status', $purchaseStatus))
            ->when($vendorId !== '', fn (Builder $query) => $query->where('vendor_id', $vendorId))
            ->when(filled($search), function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('purchase_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('vendor', fn (Builder $vendorQuery) => $vendorQuery->where('name', 'like', sprintf('%%%s%%', $search)));
                });
            })
            ->when(is_numeric($minTotal), fn (Builder $query) => $query->where('grand_total', '>=', (float) $minTotal))
            ->when(is_numeric($maxTotal), fn (Builder $query) => $query->where('grand_total', '<=', (float) $maxTotal));

        $salePaymentsBase = SalePayment::query()
            ->with(['sale.customer', 'receiver'])
            ->where('branch_id', $branchId)
            ->when($dateFromAt !== null, fn (Builder $query) => $query->where('paid_at', '>=', $dateFromAt))
            ->when($dateToAt !== null, fn (Builder $query) => $query->where('paid_at', '<=', $dateToAt))
            ->when($paymentMethod !== '', fn (Builder $query) => $query->where('payment_method', $paymentMethod))
            ->when($customerId !== '', fn (Builder $query) => $query->whereHas('sale', fn (Builder $saleQuery) => $saleQuery->where('customer_id', $customerId)))
            ->when(filled($search), function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('reference_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('sale', fn (Builder $saleQuery) => $saleQuery->where('invoice_no', 'like', sprintf('%%%s%%', $search)));
                });
            });

        $vendorPaymentsBase = VendorPayment::query()
            ->with(['vendor', 'purchase', 'creator'])
            ->where('branch_id', $branchId)
            ->when($dateFromAt !== null, fn (Builder $query) => $query->where('paid_at', '>=', $dateFromAt))
            ->when($dateToAt !== null, fn (Builder $query) => $query->where('paid_at', '<=', $dateToAt))
            ->when($paymentMethod !== '', fn (Builder $query) => $query->where('payment_method', $paymentMethod))
            ->when($vendorId !== '', fn (Builder $query) => $query->where('vendor_id', $vendorId))
            ->when(filled($search), function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('payment_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('reference_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('vendor', fn (Builder $vendorQuery) => $vendorQuery->where('name', 'like', sprintf('%%%s%%', $search)));
                });
            });

        $latestProductVendorBase = PurchaseItem::query()
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->select([
                'purchase_items.product_id',
                'purchases.vendor_id',
                DB::raw('ROW_NUMBER() OVER (PARTITION BY purchase_items.product_id ORDER BY purchases.purchase_date DESC, purchases.created_at DESC) AS vendor_rank'),
            ])
            ->where('purchase_items.branch_id', $branchId)
            ->where('purchases.branch_id', $branchId);

        $vendorProductSalesBase = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->leftJoinSub($latestProductVendorBase, 'latest_product_vendors', function ($join): void {
                $join
                    ->on('latest_product_vendors.product_id', '=', 'sale_items.product_id')
                    ->where('latest_product_vendors.vendor_rank', '=', 1);
            })
            ->leftJoin('vendors', 'vendors.id', '=', 'latest_product_vendors.vendor_id')
            ->select([
                'sale_items.product_id',
                'products.name as product_name',
                'products.sku as product_sku',
                'latest_product_vendors.vendor_id',
                'vendors.name as vendor_name',
            ])
            ->selectRaw('SUM(sale_items.qty) as total_qty_sold')
            ->selectRaw('SUM(sale_items.line_total) as total_sales_amount')
            ->selectRaw('COUNT(DISTINCT sale_items.sale_id) as invoices_count')
            ->where('sale_items.branch_id', $branchId)
            ->where('sales.branch_id', $branchId)
            ->where('sale_items.line_type', 'product')
            ->whereNotNull('sale_items.product_id')
            ->when($dateFrom !== '', fn (Builder $query) => $query->whereDate('sales.invoice_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn (Builder $query) => $query->whereDate('sales.invoice_date', '<=', $dateTo))
            ->when($vendorId !== '', fn (Builder $query) => $query->where('latest_product_vendors.vendor_id', $vendorId))
            ->when(filled($search), function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('products.name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('products.sku', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('vendors.name', 'like', sprintf('%%%s%%', $search));
                });
            })
            ->groupBy([
                'sale_items.product_id',
                'products.name',
                'products.sku',
                'latest_product_vendors.vendor_id',
                'vendors.name',
            ]);

        $categorySalesBase = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->select([
                'categories.id as category_id',
                'categories.name as category_name',
            ])
            ->selectRaw('SUM(sale_items.qty) as total_qty_sold')
            ->selectRaw('SUM(sale_items.line_total) as total_sales_amount')
            ->selectRaw('COUNT(DISTINCT sale_items.sale_id) as invoices_count')
            ->selectRaw('COUNT(DISTINCT sale_items.product_id) as products_count')
            ->where('sale_items.branch_id', $branchId)
            ->where('sales.branch_id', $branchId)
            ->where('sale_items.line_type', 'product')
            ->whereNotNull('sale_items.product_id')
            ->when($dateFrom !== '', fn (Builder $query) => $query->whereDate('sales.invoice_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn (Builder $query) => $query->whereDate('sales.invoice_date', '<=', $dateTo))
            ->when($saleStatus !== '', fn (Builder $query) => $query->where('sales.status', $saleStatus))
            ->when($invoiceType !== '', fn (Builder $query) => $query->where('sales.invoice_type', $invoiceType))
            ->when(filled($search), function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('categories.name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('products.name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('products.sku', 'like', sprintf('%%%s%%', $search));
                });
            })
            ->groupBy([
                'categories.id',
                'categories.name',
            ]);

        if ($paginate) {
            $sales = (clone $salesBase)->latest('invoice_date')->paginate(15, ['*'], 'sales_page')->withQueryString();
            $purchases = (clone $purchasesBase)->latest('purchase_date')->paginate(15, ['*'], 'purchases_page')->withQueryString();
            $salePayments = (clone $salePaymentsBase)->latest('paid_at')->paginate(15, ['*'], 'sale_payments_page')->withQueryString();
            $vendorPayments = (clone $vendorPaymentsBase)->latest('paid_at')->paginate(15, ['*'], 'vendor_payments_page')->withQueryString();
            $receivables = (clone $salesBase)->where('balance_due', '>', 0)->latest('invoice_date')->paginate(15, ['*'], 'receivables_page')->withQueryString();
            $payables = (clone $purchasesBase)
                ->whereRaw('('.$purchaseOutstandingExpression.') > 0')
                ->latest('purchase_date')
                ->paginate(15, ['*'], 'payables_page')
                ->withQueryString();
            $vendorProductSales = (clone $vendorProductSalesBase)
                ->orderByDesc('total_qty_sold')
                ->orderByDesc('total_sales_amount')
                ->paginate(15, ['*'], 'vendor_product_sales_page')
                ->withQueryString();
            $categorySales = (clone $categorySalesBase)
                ->orderByDesc('total_qty_sold')
                ->orderByDesc('total_sales_amount')
                ->paginate(15, ['*'], 'category_sales_page')
                ->withQueryString();
        } else {
            $sales = (clone $salesBase)->latest('invoice_date')->get();
            $purchases = (clone $purchasesBase)->latest('purchase_date')->get();
            $salePayments = (clone $salePaymentsBase)->latest('paid_at')->get();
            $vendorPayments = (clone $vendorPaymentsBase)->latest('paid_at')->get();
            $receivables = (clone $salesBase)->where('balance_due', '>', 0)->latest('invoice_date')->get();
            $payables = (clone $purchasesBase)
                ->whereRaw('('.$purchaseOutstandingExpression.') > 0')
                ->latest('purchase_date')
                ->get();
            $vendorProductSales = (clone $vendorProductSalesBase)
                ->orderByDesc('total_qty_sold')
                ->orderByDesc('total_sales_amount')
                ->get();
            $categorySales = (clone $categorySalesBase)
                ->orderByDesc('total_qty_sold')
                ->orderByDesc('total_sales_amount')
                ->get();
        }

        $payablesTotal = (float) ((clone $purchasesBase)
            ->selectRaw('SUM(CASE WHEN ('.$purchaseOutstandingExpression.') > 0 THEN ('.$purchaseOutstandingExpression.') ELSE 0 END) as payables_total')
            ->value('payables_total') ?? 0);

        $summary = [
            'sales_count' => (clone $salesBase)->count(),
            'sales_total' => (float) (clone $salesBase)->sum('grand_total'),
            'purchases_count' => (clone $purchasesBase)->count(),
            'purchases_total' => (float) (clone $purchasesBase)->sum('grand_total'),
            'sale_payments_total' => (float) (clone $salePaymentsBase)->sum('amount'),
            'vendor_payments_total' => (float) (clone $vendorPaymentsBase)->sum('amount'),
            'receivables_total' => (float) (clone $salesBase)->where('balance_due', '>', 0)->sum('balance_due'),
            'payables_total' => $payablesTotal,
        ];

        return [
            'sales' => $sales,
            'purchases' => $purchases,
            'salePayments' => $salePayments,
            'vendorPayments' => $vendorPayments,
            'receivables' => $receivables,
            'payables' => $payables,
            'vendorProductSales' => $vendorProductSales,
            'categorySales' => $categorySales,
            'summary' => $summary,
            'customers' => Customer::query()->where('branch_id', $branchId)->orderBy('name')->get(),
            'vendors' => Vendor::query()->where('branch_id', $branchId)->orderBy('name')->get(),
            'categories' => Category::query()->orderBy('name')->get(),
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

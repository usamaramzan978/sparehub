<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\CustomerStatus;
use App\Enums\JobCardStatus;
use App\Enums\PaymentMethodType;
use App\Enums\PurchaseStatus;
use App\Enums\RecordStatus;
use App\Enums\SaleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\DashboardFilterRequest;
use App\Models\Customer;
use App\Models\InventoryStock;
use App\Models\JobCard;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\Vendor;
use App\Models\VendorPayment;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;

final class DashboardController extends Controller
{
    public function __invoke(DashboardFilterRequest $request): View
    {
        $branchId = $this->currentBranchId();

        [$startDate, $endDate] = $this->resolveDateRange($request);
        $periodDays = (int) $startDate
            ->copy()
            ->startOfDay()
            ->diffInDays($endDate->copy()->startOfDay()) + 1;

        $salesBase = Sale::query()
            ->where('branch_id', $branchId)
            ->whereBetween('invoice_date', [$startDate->toDateString(), $endDate->toDateString()]);

        $purchasesBase = Purchase::query()
            ->where('branch_id', $branchId)
            ->whereBetween('purchase_date', [$startDate->toDateString(), $endDate->toDateString()]);

        $salePaymentsBase = SalePayment::query()
            ->where('branch_id', $branchId)
            ->whereBetween('paid_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()]);

        $vendorPaymentsBase = VendorPayment::query()
            ->where('branch_id', $branchId)
            ->whereBetween('paid_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()]);

        $salesTotal = (float) (clone $salesBase)->sum('grand_total');
        $purchasesTotal = (float) (clone $purchasesBase)->sum('grand_total');
        $salePaymentsTotal = (float) (clone $salePaymentsBase)->sum('amount');
        $vendorPaymentsTotal = (float) (clone $vendorPaymentsBase)->sum('amount');
        $grossMarginValue = $salesTotal - $purchasesTotal;

        [$previousStartDate, $previousEndDate] = $this->resolvePreviousPeriod($startDate, $periodDays);

        $previousSalesTotal = (float) Sale::query()
            ->where('branch_id', $branchId)
            ->whereBetween('invoice_date', [$previousStartDate->toDateString(), $previousEndDate->toDateString()])
            ->sum('grand_total');

        $previousPurchasesTotal = (float) Purchase::query()
            ->where('branch_id', $branchId)
            ->whereBetween('purchase_date', [$previousStartDate->toDateString(), $previousEndDate->toDateString()])
            ->sum('grand_total');

        $summary = [
            'sales_total' => $salesTotal,
            'sales_count' => (clone $salesBase)->count(),
            'purchases_total' => $purchasesTotal,
            'purchases_count' => (clone $purchasesBase)->count(),
            'receivables_total' => (float) (clone $salesBase)->where('balance_due', '>', 0)->sum('balance_due'),
            'payables_total' => (float) (clone $purchasesBase)->where('balance_due', '>', 0)->sum('balance_due'),
            'sale_payments_total' => $salePaymentsTotal,
            'vendor_payments_total' => $vendorPaymentsTotal,
            'cashflow_net' => $salePaymentsTotal - $vendorPaymentsTotal,
            'gross_margin_value' => $grossMarginValue,
            'gross_margin_percent' => $salesTotal > 0 ? ($grossMarginValue / $salesTotal) * 100 : 0,
            'sales_growth_percent' => $this->calculateGrowthPercent($salesTotal, $previousSalesTotal),
            'purchase_growth_percent' => $this->calculateGrowthPercent($purchasesTotal, $previousPurchasesTotal),
            'active_customers_count' => Customer::query()
                ->where('branch_id', $branchId)
                ->where('status', CustomerStatus::ACTIVE->value)
                ->count(),
            'active_vendors_count' => Vendor::query()
                ->where('branch_id', $branchId)
                ->where('status', RecordStatus::ACTIVE->value)
                ->count(),
            'open_job_cards_count' => JobCard::query()
                ->where('branch_id', $branchId)
                ->whereNotIn('status', [JobCardStatus::CLOSED->value, JobCardStatus::CANCELLED->value])
                ->count(),
        ];

        $bucketFormat = $periodDays > 62 ? 'Y-m' : 'Y-m-d';
        $bucketLabelFormat = $periodDays > 62 ? 'M Y' : 'd M';

        $salesBuckets = (clone $salesBase)
            ->get(['invoice_date', 'grand_total'])
            ->groupBy(fn (Sale $sale): string => $sale->invoice_date?->format($bucketFormat) ?? '')
            ->map(fn ($sales): float => (float) $sales->sum('grand_total'));

        $purchaseBuckets = (clone $purchasesBase)
            ->get(['purchase_date', 'grand_total'])
            ->groupBy(fn (Purchase $purchase): string => $purchase->purchase_date?->format($bucketFormat) ?? '')
            ->map(fn ($purchases): float => (float) $purchases->sum('grand_total'));

        $periodStart = $periodDays > 62 ? $startDate->copy()->startOfMonth() : $startDate->copy();
        $periodEnd = $periodDays > 62 ? $endDate->copy()->startOfMonth() : $endDate->copy();
        $interval = $periodDays > 62 ? '1 month' : '1 day';
        $trendPeriod = CarbonPeriod::create($periodStart, $interval, $periodEnd);

        $trendLabels = [];
        $salesTrend = [];
        $purchaseTrend = [];

        foreach ($trendPeriod as $date) {
            $key = $date->format($bucketFormat);
            $trendLabels[] = $date->format($bucketLabelFormat);
            $salesTrend[] = round((float) ($salesBuckets[$key] ?? 0), 2);
            $purchaseTrend[] = round((float) ($purchaseBuckets[$key] ?? 0), 2);
        }

        $saleStatusCounts = collect(SaleStatus::cases())
            ->map(fn (SaleStatus $status): array => [
                'label' => Str::headline($status->value),
                'value' => (clone $salesBase)->where('status', $status->value)->count(),
            ]);

        $purchaseStatusCounts = collect(PurchaseStatus::cases())
            ->map(fn (PurchaseStatus $status): array => [
                'label' => Str::headline($status->value),
                'value' => (clone $purchasesBase)->where('status', $status->value)->count(),
            ]);

        $paymentMethodTotals = (clone $salePaymentsBase)
            ->selectRaw('payment_method, SUM(amount) as total_amount')
            ->groupBy('payment_method')
            ->pluck('total_amount', 'payment_method');

        $paymentMix = collect(PaymentMethodType::cases())
            ->map(fn (PaymentMethodType $method): array => [
                'label' => Str::headline($method->value),
                'value' => round((float) ($paymentMethodTotals[$method->value] ?? 0), 2),
            ])
            ->filter(fn (array $item): bool => $item['value'] > 0)
            ->values();

        if ($paymentMix->isEmpty()) {
            $paymentMix = collect([['label' => 'No Payments', 'value' => 1.0]]);
        }

        $topCustomers = Sale::query()
            ->with(['customer:id,name'])
            ->where('branch_id', $branchId)
            ->whereBetween('invoice_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereNotNull('customer_id')
            ->selectRaw('customer_id, COUNT(*) as invoice_count, SUM(grand_total) as sales_total')
            ->groupBy('customer_id')
            ->orderByDesc('sales_total')
            ->limit(6)
            ->get();

        $topVendors = Purchase::query()
            ->with(['vendor:id,name'])
            ->where('branch_id', $branchId)
            ->whereBetween('purchase_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->selectRaw('vendor_id, COUNT(*) as purchase_count, SUM(grand_total) as purchases_total')
            ->groupBy('vendor_id')
            ->orderByDesc('purchases_total')
            ->limit(6)
            ->get();

        $lowStockItems = InventoryStock::query()
            ->with(['product:id,name,sku,track_stock'])
            ->where('branch_id', $branchId)
            ->selectRaw('product_id, SUM(qty_on_hand) as qty_on_hand, SUM(qty_reserved) as qty_reserved')
            ->groupBy('product_id')
            ->havingRaw('SUM(qty_on_hand) <= 5')
            ->orderBy('qty_on_hand')
            ->limit(8)
            ->get()
            ->filter(fn (InventoryStock $stock): bool => $stock->product instanceof Product && (bool) $stock->product->track_stock)
            ->values();

        $topStockItems = InventoryStock::query()
            ->with(['product:id,name,sku,track_stock'])
            ->where('branch_id', $branchId)
            ->selectRaw('product_id, SUM(qty_on_hand) as qty_on_hand, SUM(qty_reserved) as qty_reserved')
            ->groupBy('product_id')
            ->orderByDesc('qty_on_hand')
            ->limit(8)
            ->get()
            ->filter(fn (InventoryStock $stock): bool => $stock->product instanceof Product && (bool) $stock->product->track_stock)
            ->values();

        $recentSales = Sale::query()
            ->with(['customer:id,name'])
            ->where('branch_id', $branchId)
            ->whereBetween('invoice_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->latest('invoice_date')
            ->limit(6)
            ->get();

        $recentPurchases = Purchase::query()
            ->with(['vendor:id,name'])
            ->where('branch_id', $branchId)
            ->whereBetween('purchase_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->latest('purchase_date')
            ->limit(6)
            ->get();

        return view('tenants.dashboard', [
            'summary' => $summary,
            'dateRange' => [
                'date_from' => $startDate->toDateString(),
                'date_to' => $endDate->toDateString(),
                'label' => sprintf('%s to %s', $startDate->format('d M Y'), $endDate->format('d M Y')),
            ],
            'topCustomers' => $topCustomers,
            'topVendors' => $topVendors,
            'lowStockItems' => $lowStockItems,
            'topStockItems' => $topStockItems,
            'recentSales' => $recentSales,
            'recentPurchases' => $recentPurchases,
            'chartData' => [
                'trend_labels' => $trendLabels,
                'sales_trend' => $salesTrend,
                'purchase_trend' => $purchaseTrend,
                'payment_labels' => $paymentMix->pluck('label')->all(),
                'payment_values' => $paymentMix->pluck('value')->all(),
                'sale_status_labels' => $saleStatusCounts->pluck('label')->all(),
                'sale_status_values' => $saleStatusCounts->pluck('value')->all(),
                'purchase_status_labels' => $purchaseStatusCounts->pluck('label')->all(),
                'purchase_status_values' => $purchaseStatusCounts->pluck('value')->all(),
            ],
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveDateRange(DashboardFilterRequest $request): array
    {
        $dateFrom = mb_trim($request->string('date_from')->toString());
        $dateTo = mb_trim($request->string('date_to')->toString());

        $startDate = $dateFrom !== '' ? Date::parse($dateFrom)->startOfDay() : now()->subDays(29)->startOfDay();
        $endDate = $dateTo !== '' ? Date::parse($dateTo)->endOfDay() : now()->endOfDay();

        if ($dateFrom !== '' && $dateTo === '') {
            $endDate = Date::parse($dateFrom)->endOfDay();
        }

        if ($dateTo !== '' && $dateFrom === '') {
            $startDate = Date::parse($dateTo)->startOfDay();
        }

        return [$startDate, $endDate];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolvePreviousPeriod(Carbon $startDate, int $periodDays): array
    {
        $previousEndDate = $startDate->copy()->subDay()->endOfDay();
        $previousStartDate = $previousEndDate->copy()->subDays($periodDays - 1)->startOfDay();

        return [$previousStartDate, $previousEndDate];
    }

    private function calculateGrowthPercent(float $current, float $previous): float
    {
        if ($previous === 0.0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }
}

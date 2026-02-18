<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\SaleStatus;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class SalesTreeController extends Controller
{
    public function __invoke(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $dateFrom = mb_trim($request->string('date_from')->toString());
        $dateTo = mb_trim($request->string('date_to')->toString());
        $search = mb_trim($request->string('search')->toString());
        $customerId = mb_trim($request->string('customer_id')->toString());
        $status = mb_trim($request->string('status')->toString());

        $sales = Sale::query()
            ->with([
                'customer:id,name',
                'items.product:id,name,sku',
                'items.serviceCatalog:id,name,code',
            ])
            ->where('branch_id', $branchId)
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('invoice_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('invoice_date', '<=', $dateTo))
            ->when($customerId !== '', fn ($query) => $query->where('customer_id', $customerId))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder
                        ->where('invoice_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('name', 'like', sprintf('%%%s%%', $search)))
                        ->orWhereHas('items.product', fn ($productQuery) => $productQuery->where('name', 'like', sprintf('%%%s%%', $search)))
                        ->orWhereHas('items.serviceCatalog', fn ($serviceQuery) => $serviceQuery->where('name', 'like', sprintf('%%%s%%', $search)));
                });
            })
            ->latest('invoice_date')
            ->limit(500)
            ->get();

        $groupedSales = $sales
            ->groupBy(fn (Sale $sale): string => (string) ($sale->customer_id ?: 'walk-in'))
            ->map(function (Collection $customerSales, string $groupKey): array {
                $firstSale = $customerSales->first();
                $customerName = $groupKey === 'walk-in'
                    ? 'Walk-in / No Customer'
                    : (string) ($firstSale?->customer?->name ?: 'Unknown Customer');

                $invoices = $customerSales
                    ->sortByDesc(fn (Sale $sale): string => (string) ($sale->invoice_date?->format('Y-m-d') ?: ''))
                    ->values()
                    ->map(function (Sale $sale): array {
                        $itemsCount = $sale->items->count();
                        $qtyTotal = (float) $sale->items->sum(fn (SaleItem $item): float => (float) $item->qty);

                        return [
                            'sale' => $sale,
                            'items_count' => $itemsCount,
                            'qty_total' => $qtyTotal,
                        ];
                    });

                return [
                    'group_key' => $groupKey,
                    'customer_name' => $customerName,
                    'invoices_count' => $customerSales->count(),
                    'grand_total' => (float) $customerSales->sum(fn (Sale $sale): float => (float) $sale->grand_total),
                    'paid_total' => (float) $customerSales->sum(fn (Sale $sale): float => (float) $sale->paid_total),
                    'balance_due' => (float) $customerSales->sum(fn (Sale $sale): float => (float) $sale->balance_due),
                    'items_count' => $customerSales->sum(fn (Sale $sale): int => $sale->items->count()),
                    'invoices' => $invoices,
                ];
            })
            ->sortByDesc('grand_total')
            ->values();

        $summary = [
            'customers_count' => $groupedSales->count(),
            'invoices_count' => $sales->count(),
            'items_count' => $sales->sum(fn (Sale $sale): int => $sale->items->count()),
            'grand_total' => (float) $sales->sum(fn (Sale $sale): float => (float) $sale->grand_total),
            'paid_total' => (float) $sales->sum(fn (Sale $sale): float => (float) $sale->paid_total),
            'balance_due' => (float) $sales->sum(fn (Sale $sale): float => (float) $sale->balance_due),
        ];

        return view('tenants.sales-tree.index', [
            'groupedSales' => $groupedSales,
            'summary' => $summary,
            'customers' => Customer::query()->where('branch_id', $branchId)->orderBy('name')->get(['id', 'name']),
            'statuses' => SaleStatus::cases(),
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'search' => $search,
                'customer_id' => $customerId,
                'status' => $status,
            ],
        ]);
    }
}

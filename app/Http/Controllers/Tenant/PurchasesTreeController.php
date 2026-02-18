<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\PurchaseStatus;
use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Vendor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class PurchasesTreeController extends Controller
{
    public function __invoke(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $dateFrom = mb_trim($request->string('date_from')->toString());
        $dateTo = mb_trim($request->string('date_to')->toString());
        $search = mb_trim($request->string('search')->toString());
        $vendorId = mb_trim($request->string('vendor_id')->toString());
        $status = mb_trim($request->string('status')->toString());

        $purchases = Purchase::query()
            ->with([
                'vendor:id,name',
                'warehouse:id,name,code',
                'items.product:id,name,sku',
            ])
            ->where('branch_id', $branchId)
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('purchase_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('purchase_date', '<=', $dateTo))
            ->when($vendorId !== '', fn ($query) => $query->where('vendor_id', $vendorId))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder
                        ->where('purchase_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('vendor_invoice_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('vendor', fn ($vendorQuery) => $vendorQuery->where('name', 'like', sprintf('%%%s%%', $search)))
                        ->orWhereHas('items.product', fn ($productQuery) => $productQuery->where('name', 'like', sprintf('%%%s%%', $search)));
                });
            })
            ->latest('purchase_date')
            ->limit(500)
            ->get();

        $groupedPurchases = $purchases
            ->groupBy(fn (Purchase $purchase): string => (string) ($purchase->vendor_id ?: 'no-vendor'))
            ->map(function (Collection $vendorPurchases, string $groupKey): array {
                $firstPurchase = $vendorPurchases->first();
                $vendorName = $groupKey === 'no-vendor'
                    ? 'No Vendor'
                    : (string) ($firstPurchase?->vendor?->name ?: 'Unknown Vendor');

                $entries = $vendorPurchases
                    ->sortByDesc(fn (Purchase $purchase): string => (string) ($purchase->purchase_date?->format('Y-m-d') ?: ''))
                    ->values()
                    ->map(function (Purchase $purchase): array {
                        $itemsCount = $purchase->items->count();
                        $qtyTotal = (float) $purchase->items->sum(fn (PurchaseItem $item): float => (float) $item->qty);

                        return [
                            'purchase' => $purchase,
                            'items_count' => $itemsCount,
                            'qty_total' => $qtyTotal,
                        ];
                    });

                return [
                    'group_key' => $groupKey,
                    'vendor_name' => $vendorName,
                    'purchases_count' => $vendorPurchases->count(),
                    'grand_total' => (float) $vendorPurchases->sum(fn (Purchase $purchase): float => (float) $purchase->grand_total),
                    'paid_total' => (float) $vendorPurchases->sum(fn (Purchase $purchase): float => (float) $purchase->paid_total),
                    'balance_due' => (float) $vendorPurchases->sum(fn (Purchase $purchase): float => (float) $purchase->balance_due),
                    'items_count' => $vendorPurchases->sum(fn (Purchase $purchase): int => $purchase->items->count()),
                    'purchases' => $entries,
                ];
            })
            ->sortByDesc('grand_total')
            ->values();

        $summary = [
            'vendors_count' => $groupedPurchases->count(),
            'purchases_count' => $purchases->count(),
            'items_count' => $purchases->sum(fn (Purchase $purchase): int => $purchase->items->count()),
            'grand_total' => (float) $purchases->sum(fn (Purchase $purchase): float => (float) $purchase->grand_total),
            'paid_total' => (float) $purchases->sum(fn (Purchase $purchase): float => (float) $purchase->paid_total),
            'balance_due' => (float) $purchases->sum(fn (Purchase $purchase): float => (float) $purchase->balance_due),
        ];

        return view('tenants.purchases-tree.index', [
            'groupedPurchases' => $groupedPurchases,
            'summary' => $summary,
            'vendors' => Vendor::query()->where('branch_id', $branchId)->orderBy('name')->get(['id', 'name']),
            'statuses' => PurchaseStatus::cases(),
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'search' => $search,
                'vendor_id' => $vendorId,
                'status' => $status,
            ],
        ]);
    }
}

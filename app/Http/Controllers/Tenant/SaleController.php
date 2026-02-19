<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\InvoiceType;
use App\Enums\SaleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\SaleRequest;
use App\Models\Customer;
use App\Models\InventoryStock;
use App\Models\JobCard;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\ServiceCatalog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class SaleController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $perPage = min(max($request->integer('per_page', 15), 5), 100);
        $search = mb_trim($request->string('search')->toString());

        $sales = Sale::query()
            ->with(['customer', 'jobCard'])
            ->where('branch_id', $branchId)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('invoice_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('customer', fn (Builder $q) => $q->where('name', 'like', sprintf('%%%s%%', $search)));
                });
            })
            ->latest('invoice_date')
            ->paginate($perPage)
            ->withQueryString();

        return view('tenants.sales.index', [
            'items' => $sales,
        ]);
    }

    public function create(): View
    {
        return view('tenants.sales.create', $this->formOptions());
    }

    public function store(SaleRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $branchId = $this->currentBranchId();
        $items = $payload['items'];
        unset($payload['items']);

        $payload['branch_id'] = $branchId;
        $payload['created_by'] = auth('user')->id();

        Sale::query()->getConnection()->transaction(function () use ($payload, $items, $branchId): void {
            $sale = Sale::query()->create($payload);
            $this->syncSaleItems($sale, $items, $branchId);
        });

        return to_route('tenant.sales.index')->with('status', 'Created.');
    }

    public function show(Sale $sale): View
    {
        $this->ensureSaleInCurrentBranch($sale);

        $sale->load([
            'customer',
            'jobCard',
            'creator',
            'items.product',
            'items.serviceCatalog',
            'items.jobCardService',
            'items.mechanic',
            'payments.receiver',
        ]);

        return view('tenants.sales.show', [
            'sale' => $sale,
        ]);
    }

    public function print(Sale $sale): View
    {
        $this->ensureSaleInCurrentBranch($sale);

        $sale->load([
            'branch',
            'customer',
            'items.product',
            'items.serviceCatalog',
            'payments',
        ]);

        return view('tenants.sales.print', [
            'sale' => $sale,
        ]);
    }

    public function edit(Sale $sale): View
    {
        $this->ensureSaleInCurrentBranch($sale);
        $sale->load('items');

        return view('tenants.sales.edit', array_merge(
            ['sale' => $sale],
            $this->formOptions()
        ));
    }

    public function update(SaleRequest $request, Sale $sale): RedirectResponse
    {
        $this->ensureSaleInCurrentBranch($sale);

        $payload = $request->validated();
        $branchId = $this->currentBranchId();
        $items = $payload['items'];
        unset($payload['items']);

        $payload['branch_id'] = $branchId;

        Sale::query()->getConnection()->transaction(function () use ($sale, $payload, $items, $branchId): void {
            $sale->update($payload);
            $this->syncSaleItems($sale, $items, $branchId);
        });

        return to_route('tenant.sales.index')->with('status', 'Updated.');
    }

    public function destroy(Sale $sale): RedirectResponse
    {
        $this->ensureSaleInCurrentBranch($sale);
        $this->syncStockForSaleItems($sale->items()->get(), $sale->branch_id, reverse: true);
        $sale->delete();

        return to_route('tenant.sales.index')->with('status', 'Deleted.');
    }

    private function ensureSaleInCurrentBranch(Sale $sale): void
    {
        abort_if($sale->branch_id !== $this->currentBranchId(), 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        $branchId = $this->currentBranchId();

        return [
            'customers' => Customer::query()->where('branch_id', $branchId)->orderBy('name')->get(),
            'jobCards' => JobCard::query()->where('branch_id', $branchId)->latest('job_date')->get(),
            'products' => Product::query()->orderBy('name')->get(),
            'serviceCatalogs' => ServiceCatalog::query()->where('branch_id', $branchId)->orderBy('name')->get(),
            'mechanics' => User::query()->where('branch_id', $branchId)->active()->orderBy('name')->get(['id', 'name']),
            'statuses' => SaleStatus::cases(),
            'invoiceTypes' => InvoiceType::cases(),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncSaleItems(Sale $sale, array $items, string $branchId): void
    {
        $existingItems = $sale->items()->get();
        $this->syncStockForSaleItems($existingItems, $branchId, reverse: true);
        $sale->items()->delete();

        foreach ($items as $item) {
            $qty = (float) $item['qty'];
            $unitPrice = (float) $item['unit_price'];
            $discountAmount = (float) ($item['discount_amount'] ?? 0);
            $taxAmount = (float) ($item['tax_amount'] ?? 0);
            $lineTotal = ($qty * $unitPrice) - $discountAmount + $taxAmount;

            $lineType = (string) $item['line_type'];
            $productId = $lineType === 'product' ? $item['product_id'] : null;
            $serviceCatalogId = $lineType === 'service' ? $item['service_catalog_id'] : null;
            $mechanicId = $lineType === 'service' ? ($item['mechanic_id'] ?? null) : null;
            $mechanicCharge = $lineType === 'service' ? (float) ($item['mechanic_charge'] ?? 0) : 0.0;

            SaleItem::query()->create([
                'sale_id' => $sale->id,
                'branch_id' => $branchId,
                'product_id' => $productId,
                'service_catalog_id' => $serviceCatalogId,
                'job_card_service_id' => null,
                'mechanic_id' => $mechanicId,
                'line_type' => $lineType,
                'description' => $item['description'] ?? null,
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'mechanic_charge' => $mechanicCharge,
                'line_total' => $lineTotal,
            ]);
        }

        $createdItems = $sale->items()->get();
        $this->syncStockForSaleItems($createdItems, $branchId);
        $subTotal = (float) $createdItems->sum(fn (SaleItem $item): float => (float) $item->qty * (float) $item->unit_price);
        $discountTotal = (float) $createdItems->sum(fn (SaleItem $item): float => (float) $item->discount_amount);
        $taxTotal = (float) $createdItems->sum(fn (SaleItem $item): float => (float) $item->tax_amount);
        $grandTotal = (float) $createdItems->sum(fn (SaleItem $item): float => (float) $item->line_total);

        $sale->update([
            'sub_total' => $subTotal,
            'discount_total' => $discountTotal,
            'tax_total' => $taxTotal,
            'grand_total' => $grandTotal,
            'balance_due' => $grandTotal - (float) $sale->paid_total,
        ]);
    }

    /**
     * @param  Collection<int, SaleItem>  $saleItems
     */
    private function syncStockForSaleItems(Collection $saleItems, string $branchId, bool $reverse = false): void
    {
        $qtyByProduct = $saleItems
            ->filter(fn (SaleItem $item): bool => $item->product_id !== null)
            ->groupBy('product_id')
            ->map(fn (Collection $items): float => (float) $items->sum('qty'));

        if ($qtyByProduct->isEmpty()) {
            return;
        }

        $trackedProductIds = Product::query()
            ->whereIn('id', $qtyByProduct->keys()->all())
            ->where('track_stock', true)
            ->pluck('id')
            ->all();

        if ($trackedProductIds === []) {
            return;
        }

        foreach ($trackedProductIds as $productId) {
            $qty = (float) ($qtyByProduct->get($productId) ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $stockRow = InventoryStock::query()
                ->where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->oldest('updated_at')
                ->first();

            if (! $stockRow instanceof InventoryStock) {
                continue;
            }

            $adjustedQty = $reverse
                ? (float) $stockRow->qty_on_hand + $qty
                : (float) $stockRow->qty_on_hand - $qty;

            $stockRow->update([
                'qty_on_hand' => $adjustedQty,
            ]);
        }
    }
}

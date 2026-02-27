<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\InvoiceType;
use App\Enums\PaymentMethodType;
use App\Enums\RecordStatus;
use App\Enums\SaleLineType;
use App\Enums\SaleStatus;
use App\Enums\ServiceCatalogType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\PosStoreRequest;
use App\Models\Category;
use App\Models\Customer;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\ServiceCatalog;
use App\Models\Tax;
use App\Models\TenantSetting;
use App\Models\User;
use App\Support\AuditTimelineLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

final class PosController extends Controller
{
    public function index(): View
    {
        $branchId = $this->currentBranchId();

        $customers = Customer::query()->where('branch_id', $branchId)->orderBy('name')->get();
        $categories = Category::query()
            ->where('status', RecordStatus::ACTIVE->value)
            ->orderBy('name')
            ->get();
        $statuses = [
            SaleStatus::POSTED,
            SaleStatus::DRAFT,
            SaleStatus::HOLD,
        ];
        $paymentMethods = PaymentMethodType::cases();
        $mechanics = User::query()
            ->where('branch_id', $branchId)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name']);
        $tenantSettings = TenantSetting::query()->first();

        return view('tenants.pos.index', [
            'customers' => $customers,
            'categories' => $categories,
            'paymentMethods' => $paymentMethods,
            'mechanics' => $mechanics,
            'statuses' => $statuses,
            'tenantSettings' => $tenantSettings,
        ]);
    }

    public function scan(Request $request): JsonResponse
    {
        $query = mb_trim($request->string('query')->toString());

        if ($query === '') {
            return response()->json([
                'mode' => 'list',
                'items' => [],
            ]);
        }

        $items = $this->searchItems($query, 20);

        if ($items->count() === 1) {
            return response()->json([
                'mode' => 'single',
                'item' => $items->first(),
            ]);
        }

        return response()->json([
            'mode' => 'list',
            'items' => $items->values(),
        ]);
    }

    public function catalog(Request $request): JsonResponse
    {
        $branchId = $this->currentBranchId();
        $type = $request->string('type')->toString();
        $category = mb_trim($request->string('category')->toString());
        $limit = min(max($request->integer('limit', 24), 1), 120);

        if ($type === 'service') {
            $services = ServiceCatalog::query()
                ->with('defaultTax')
                ->where('branch_id', $branchId)
                ->where('status', RecordStatus::ACTIVE->value)
                ->where('type', ServiceCatalogType::Workshop->value)
                ->orderBy('name')
                ->limit($limit)
                ->get();

            return response()->json([
                'items' => $services->map(fn (ServiceCatalog $service): array => $this->mapServiceItem($service))->values(),
            ]);
        }

        if ($type !== 'product') {
            return response()->json(['items' => []]);
        }

        if ($category === '') {
            return response()->json(['items' => []]);
        }

        $products = Product::query()
            ->with(['defaultTax'])
            ->where('status', RecordStatus::ACTIVE->value)
            ->where('category_id', $category)
            ->orderBy('name')
            ->limit($limit)
            ->get();

        $productIds = $products->pluck('id')->all();
        $pricesByProduct = ProductPrice::query()
            ->where('branch_id', $branchId)
            ->whereIn('product_id', $productIds)
            ->orderByDesc('effective_from')
            ->get()
            ->groupBy('product_id');

        $stockByProduct = InventoryStock::query()
            ->where('branch_id', $branchId)
            ->whereIn('product_id', $productIds)
            ->selectRaw('product_id, SUM(qty_on_hand) as qty_on_hand')
            ->groupBy('product_id')
            ->pluck('qty_on_hand', 'product_id');

        return response()->json([
            'items' => $products->map(function (Product $product) use ($pricesByProduct, $stockByProduct): array {
                $latestPrice = $pricesByProduct->get($product->id)?->first();

                return $this->mapProductItem(
                    $product,
                    $latestPrice instanceof ProductPrice ? (float) $latestPrice->retail_price : 0.0,
                    (float) ($stockByProduct[$product->id] ?? 0.0)
                );
            })->values(),
        ]);
    }

    public function store(PosStoreRequest $request): RedirectResponse
    {
        $branchId = $this->currentBranchId();
        $validated = $request->validated();

        $items = collect($validated['items'])->map(fn (array $item): array => [
            'type' => $item['type'],
            'ref_id' => $item['ref_id'],
            'qty' => (float) $item['qty'],
            'price' => (float) $item['price'],
            'tax_rate' => (float) ($item['tax_rate'] ?? 0),
            'tax_inclusive' => (bool) ($item['tax_inclusive'] ?? false),
            'name' => (string) ($item['name'] ?? ''),
            'mechanic_id' => $item['mechanic_id'] ?? null,
            'mechanic_charge' => (float) ($item['mechanic_charge'] ?? 0),
        ]);

        $productIds = $items->where('type', 'product')->pluck('ref_id')->all();
        $serviceIds = $items->where('type', 'service')->pluck('ref_id')->all();

        $existingProducts = Product::query()->whereIn('id', $productIds)->pluck('id')->all();
        $existingServices = ServiceCatalog::query()
            ->where('branch_id', $branchId)
            ->whereIn('id', $serviceIds)
            ->pluck('id')
            ->all();
        $mechanicIds = $items
            ->pluck('mechanic_id')
            ->filter(fn ($value): bool => is_string($value) && $value !== '')
            ->values()
            ->all();
        $existingMechanics = User::query()
            ->where('branch_id', $branchId)
            ->whereIn('id', $mechanicIds)
            ->pluck('id')
            ->all();

        foreach ($items as $item) {
            if ($item['type'] === 'product' && ! in_array($item['ref_id'], $existingProducts, true)) {
                return back()->withErrors(['items' => 'One or more selected products are invalid.'])->withInput();
            }

            if ($item['type'] === 'service' && ! in_array($item['ref_id'], $existingServices, true)) {
                return back()->withErrors(['items' => 'One or more selected services are invalid.'])->withInput();
            }

            if (is_string($item['mechanic_id']) && $item['mechanic_id'] !== '' && ! in_array($item['mechanic_id'], $existingMechanics, true)) {
                return back()->withErrors(['items' => 'One or more selected mechanics are invalid.'])->withInput();
            }
        }

        $subTotal = $items->sum(fn (array $item): float => $item['qty'] * $item['price']);
        $discountType = (string) ($validated['discount_type'] ?? 'amount');
        $discountValue = (float) ($validated['discount_value'] ?? 0);
        $discountTotal = $discountType === 'percent' ? ($subTotal * $discountValue / 100) : $discountValue;
        $discountTotal = min(max($discountTotal, 0), $subTotal);

        $discountRatio = $subTotal > 0 ? ($discountTotal / $subTotal) : 0;

        $taxTotal = 0.0;
        $grandTotal = 0.0;

        $linePayload = $items->map(function (array $item) use ($discountRatio, &$taxTotal, &$grandTotal): array {
            $lineBase = $item['qty'] * $item['price'];
            $lineDiscount = $lineBase * $discountRatio;
            $lineNet = max($lineBase - $lineDiscount, 0);

            $lineTax = 0.0;
            if ($item['tax_rate'] > 0) {
                $lineTax = $item['tax_inclusive']
                    ? ($lineNet - ($lineNet / (1 + ($item['tax_rate'] / 100))))
                    : ($lineNet * $item['tax_rate'] / 100);
            }

            $lineTotal = $lineNet + ($item['tax_inclusive'] ? 0.0 : $lineTax);

            $taxTotal += $lineTax;
            $grandTotal += $lineTotal;

            return [
                'type' => $item['type'],
                'ref_id' => $item['ref_id'],
                'qty' => $item['qty'],
                'unit_price' => $item['price'],
                'discount_amount' => $lineDiscount,
                'tax_amount' => $lineTax,
                'mechanic_id' => $item['mechanic_id'],
                'mechanic_charge' => $item['mechanic_charge'],
                'line_total' => $lineTotal,
                'description' => $item['name'] !== '' ? $item['name'] : null,
            ];
        });

        $paymentMode = (string) ($validated['payment_mode'] ?? 'cash');
        $cashReceived = (float) ($validated['cash_received'] ?? 0);

        $paidTotal = $paymentMode === 'cash' || $paymentMode === 'online' ? $grandTotal : min($cashReceived, $grandTotal);

        $paidTotal = max($paidTotal, 0);

        $balanceDue = $grandTotal - $paidTotal;

        $hasProduct = $items->contains(fn (array $item): bool => $item['type'] === 'product');
        $hasService = $items->contains(fn (array $item): bool => $item['type'] === 'service');

        $invoiceType = InvoiceType::PRODUCT;
        if ($hasProduct && $hasService) {
            $invoiceType = InvoiceType::MIXED;
        } elseif (! $hasProduct && $hasService) {
            $invoiceType = InvoiceType::SERVICE;
        }

        $sale = Sale::query()->getConnection()->transaction(function () use (
            $branchId,
            $validated,
            $invoiceType,
            $subTotal,
            $discountTotal,
            $taxTotal,
            $grandTotal,
            $paidTotal,
            $balanceDue,
            $linePayload,
            $paymentMode
        ): Sale {
            $sale = Sale::query()->create([
                'branch_id' => $branchId,
                'customer_id' => $validated['customer_id'] ?? null,
                'created_by' => auth('user')->id(),
                'invoice_no' => $this->nextInvoiceNumber($branchId),
                'invoice_date' => now()->toDateString(),
                'status' => $validated['status'],
                'invoice_type' => $invoiceType->value,
                'sub_total' => $subTotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'grand_total' => $grandTotal,
                'paid_total' => $paidTotal,
                'balance_due' => $balanceDue,
                'notes' => 'Created from POS',
                'posted_at' => now(),
            ]);

            foreach ($linePayload as $line) {
                SaleItem::query()->create([
                    'sale_id' => $sale->id,
                    'branch_id' => $branchId,
                    'product_id' => $line['type'] === 'product' ? $line['ref_id'] : null,
                    'service_catalog_id' => $line['type'] === 'service' ? $line['ref_id'] : null,
                    'job_card_service_id' => null,
                    'mechanic_id' => $line['mechanic_id'],
                    'line_type' => $line['type'] === 'product' ? SaleLineType::PRODUCT->value : SaleLineType::SERVICE->value,
                    'description' => $line['description'],
                    'qty' => $line['qty'],
                    'unit_price' => $line['unit_price'],
                    'discount_amount' => $line['discount_amount'],
                    'tax_amount' => $line['tax_amount'],
                    'mechanic_charge' => $line['mechanic_charge'],
                    'line_total' => $line['line_total'],
                ]);
            }

            $this->decrementTrackedStock($branchId, $linePayload);

            if ($paidTotal > 0) {
                SalePayment::query()->create([
                    'sale_id' => $sale->id,
                    'branch_id' => $branchId,
                    'received_by' => auth('user')->id(),
                    'payment_method' => $paymentMode === 'online' ? PaymentMethodType::BANK->value : PaymentMethodType::CASH->value,
                    'amount' => $paidTotal,
                    'reference_no' => null,
                    'payment_proof_path' => null,
                    'paid_at' => now(),
                    'notes' => 'POS payment',
                ]);
            }

            return $sale;
        });

        if ($paymentMode === 'online' && $request->hasFile('payment_proof')) {
            $media = $sale->addMediaFromRequest('payment_proof')
                ->toMediaCollection('online_payment_proof');

            $sale->payments()
                ->where('payment_method', PaymentMethodType::BANK->value)
                ->latest('created_at')
                ->limit(1)
                ->update([
                    'payment_proof_path' => $media->getPathRelativeToRoot(),
                ]);
        }

        AuditTimelineLogger::log(
            event: 'pos_sale_created',
            description: 'POS sale created.',
            causer: Auth::guard('user')->user(),
            subject: $sale,
            properties: [
                'sale_id' => (string) $sale->id,
                'invoice_no' => $sale->invoice_no,
                'grand_total' => (float) $sale->grand_total,
                'paid_total' => (float) $sale->paid_total,
                'payment_mode' => $paymentMode,
            ],
        );

        $tenantRouteKey = (string) (request()->route('tenant') ?? tenant('id'));

        if ($request->boolean('print_receipt')) {
            return to_route('tenant.sales.print', [
                'tenant' => $tenantRouteKey,
                'sale' => $sale,
                'auto_print' => 1,
            ]);
        }

        return to_route('tenant.pos.index', ['tenant' => $tenantRouteKey])
            ->with('status', sprintf('POS sale %s created.', $sale->invoice_no));
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $linePayload
     */
    private function decrementTrackedStock(string $branchId, Collection $linePayload): void
    {
        $productQtyById = $linePayload
            ->where('type', 'product')
            ->groupBy('ref_id')
            ->map(fn (Collection $lines): float => (float) $lines->sum('qty'));

        if ($productQtyById->isEmpty()) {
            return;
        }

        $trackedProductIds = Product::query()
            ->whereIn('id', $productQtyById->keys()->all())
            ->where('track_stock', true)
            ->pluck('id')
            ->all();

        if ($trackedProductIds === []) {
            return;
        }

        foreach ($trackedProductIds as $productId) {
            $qty = (float) ($productQtyById->get($productId) ?? 0);
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

            $stockRow->update([
                'qty_on_hand' => (float) $stockRow->qty_on_hand - $qty,
            ]);
        }
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchItems(string $query, int $limit): Collection
    {
        $branchId = $this->currentBranchId();

        $products = Product::query()
            ->with('defaultTax')
            ->where('status', RecordStatus::ACTIVE->value)
            ->where(function ($builder) use ($query): void {
                $builder
                    ->where('name', 'like', sprintf('%%%s%%', $query))
                    ->orWhere('sku', 'like', sprintf('%%%s%%', $query))
                    ->orWhere('barcode', 'like', sprintf('%%%s%%', $query))
                    ->orWhere('part_number', 'like', sprintf('%%%s%%', $query));
            })
            ->limit($limit)
            ->get();

        $productIds = $products->pluck('id')->all();
        $pricesByProduct = ProductPrice::query()
            ->where('branch_id', $branchId)
            ->whereIn('product_id', $productIds)
            ->orderByDesc('effective_from')
            ->get()
            ->groupBy('product_id');

        $stockByProduct = InventoryStock::query()
            ->where('branch_id', $branchId)
            ->whereIn('product_id', $productIds)
            ->selectRaw('product_id, SUM(qty_on_hand) as qty_on_hand')
            ->groupBy('product_id')
            ->pluck('qty_on_hand', 'product_id');

        $productItems = $products->map(function (Product $product) use ($pricesByProduct, $stockByProduct): array {
            $latestPrice = $pricesByProduct->get($product->id)?->first();

            return $this->mapProductItem(
                $product,
                $latestPrice instanceof ProductPrice ? (float) $latestPrice->retail_price : 0.0,
                (float) ($stockByProduct[$product->id] ?? 0.0)
            );
        });

        $serviceItems = ServiceCatalog::query()
            ->with('defaultTax')
            ->where('branch_id', $branchId)
            ->where('status', RecordStatus::ACTIVE->value)
            ->where('type', ServiceCatalogType::Workshop->value)
            ->where(function ($builder) use ($query): void {
                $builder
                    ->where('name', 'like', sprintf('%%%s%%', $query))
                    ->orWhere('code', 'like', sprintf('%%%s%%', $query))
                    ->orWhere('type', 'like', sprintf('%%%s%%', $query));
            })
            ->limit($limit)
            ->get()
            ->map(fn (ServiceCatalog $service): array => $this->mapServiceItem($service));

        return $productItems->concat($serviceItems)->take($limit)->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function mapProductItem(Product $product, float $price, float $stock): array
    {
        [$taxRate, $taxInclusive] = $this->resolveTaxData($product->defaultTax);

        return [
            'id' => 'product:'.$product->id,
            'ref_id' => $product->id,
            'type' => 'product',
            'name' => $product->name,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'price' => $price,
            'tax_rate' => $taxRate,
            'tax_inclusive' => $taxInclusive,
            'stock' => $stock,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapServiceItem(ServiceCatalog $service): array
    {
        [$taxRate, $taxInclusive] = $this->resolveTaxData($service->defaultTax);

        return [
            'id' => 'service:'.$service->id,
            'ref_id' => $service->id,
            'type' => 'service',
            'name' => $service->name,
            'product_name' => ucfirst($service->type->value),
            'sku' => $service->code,
            'price' => (float) $service->base_price,
            'tax_rate' => $taxRate,
            'tax_inclusive' => $taxInclusive,
            'stock' => 999999,
        ];
    }

    /**
     * @return array{0:float,1:bool}
     */
    private function resolveTaxData(?Tax $tax): array
    {
        if (! $tax instanceof Tax) {
            return [0.0, false];
        }

        return [(float) $tax->rate, (bool) $tax->is_inclusive];
    }

    private function nextInvoiceNumber(string $branchId): string
    {
        $datePrefix = now()->format('Ymd');
        $base = 'POS-'.$datePrefix.'-';

        $last = Sale::query()
            ->withoutGlobalScope('session_branch')
            ->where('branch_id', $branchId)
            ->where('invoice_no', 'like', $base.'%')
            ->orderByDesc('invoice_no')
            ->value('invoice_no');

        $next = 1;
        if (is_string($last)) {
            $suffix = (int) mb_substr($last, mb_strlen($base));
            $next = $suffix + 1;
        }

        return $base.mb_str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}

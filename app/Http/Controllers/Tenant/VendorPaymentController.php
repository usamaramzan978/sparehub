<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\PaymentMethodType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\VendorPaymentRequest;
use App\Models\Purchase;
use App\Models\Vendor;
use App\Models\VendorPayment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class VendorPaymentController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $perPage = min(max($request->integer('per_page', 15), 5), 100);

        $items = VendorPayment::query()
            ->with(['vendor', 'purchase', 'creator'])
            ->where('branch_id', $branchId)
            ->latest('paid_at')
            ->paginate($perPage);

        return view('tenants.vendor-payments.index', [
            'items' => $items,
        ]);
    }

    public function create(): View
    {
        return view('tenants.vendor-payments.create', $this->formOptions());
    }

    public function store(VendorPaymentRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $payload['branch_id'] = $this->currentBranchId();
        $payload['created_by'] = auth('user')->id();

        $payment = VendorPayment::query()->create($payload);
        $this->recalculatePurchasePaid($payment->purchase);

        return to_route('tenant.vendor-payments.index')->with('status', 'Created.');
    }

    public function show(VendorPayment $vendorPayment): View
    {
        $this->ensureVendorPaymentInCurrentBranch($vendorPayment);

        $vendorPayment->load(['vendor', 'purchase.vendor', 'creator', 'branch']);

        return view('tenants.vendor-payments.show', [
            'vendorPayment' => $vendorPayment,
        ]);
    }

    public function edit(VendorPayment $vendorPayment): View
    {
        $this->ensureVendorPaymentInCurrentBranch($vendorPayment);

        return view('tenants.vendor-payments.edit', array_merge(
            ['vendorPayment' => $vendorPayment],
            $this->formOptions()
        ));
    }

    public function update(VendorPaymentRequest $request, VendorPayment $vendorPayment): RedirectResponse
    {
        $this->ensureVendorPaymentInCurrentBranch($vendorPayment);

        $oldPurchase = $vendorPayment->purchase;

        $payload = $request->validated();
        $payload['branch_id'] = $this->currentBranchId();

        $vendorPayment->update($payload);

        $this->recalculatePurchasePaid($oldPurchase);
        $this->recalculatePurchasePaid($vendorPayment->purchase);

        return to_route('tenant.vendor-payments.index')->with('status', 'Updated.');
    }

    public function destroy(VendorPayment $vendorPayment): RedirectResponse
    {
        $this->ensureVendorPaymentInCurrentBranch($vendorPayment);

        $purchase = $vendorPayment->purchase;
        $vendorPayment->delete();
        $this->recalculatePurchasePaid($purchase);

        return to_route('tenant.vendor-payments.index')->with('status', 'Deleted.');
    }

    private function ensureVendorPaymentInCurrentBranch(VendorPayment $vendorPayment): void
    {
        abort_if($vendorPayment->branch_id !== $this->currentBranchId(), 404);
    }

    private function recalculatePurchasePaid(?Purchase $purchase): void
    {
        if (! $purchase instanceof Purchase) {
            return;
        }

        $paid = (float) $purchase->payments()->sum('amount');
        $grandTotal = (float) $purchase->grand_total;

        $purchase->update([
            'paid_total' => $paid,
            'balance_due' => $grandTotal - $paid,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        $branchId = $this->currentBranchId();

        return [
            'vendors' => Vendor::query()->where('branch_id', $branchId)->orderBy('name')->get(),
            'purchases' => Purchase::query()->where('branch_id', $branchId)->latest('purchase_date')->get(),
            'methods' => PaymentMethodType::cases(),
        ];
    }
}

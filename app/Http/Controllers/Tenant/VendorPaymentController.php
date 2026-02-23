<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\VendorPayment\CreateVendorPaymentAction;
use App\Actions\Tenant\VendorPayment\DeleteVendorPaymentAction;
use App\Actions\Tenant\VendorPayment\EnsureVendorPaymentInBranchAction;
use App\Actions\Tenant\VendorPayment\UpdateVendorPaymentAction;
use App\Enums\PaymentMethodType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\VendorPaymentRequest;
use App\Models\Purchase;
use App\Models\Vendor;
use App\Models\VendorPayment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

    public function store(VendorPaymentRequest $request, CreateVendorPaymentAction $action): RedirectResponse
    {
        $action->handle($request->validated(), $this->currentBranchId(), auth('user')->id(), Auth::guard('user')->user());

        return to_route('tenant.vendor-payments.index')->with('status', 'Created.');
    }

    public function show(VendorPayment $vendorPayment, EnsureVendorPaymentInBranchAction $ensureVendorPaymentInBranchAction): View
    {
        $vendorPayment = $ensureVendorPaymentInBranchAction->handle($vendorPayment, $this->currentBranchId());

        $vendorPayment->load(['vendor', 'purchase.vendor', 'creator', 'branch']);

        return view('tenants.vendor-payments.show', [
            'vendorPayment' => $vendorPayment,
        ]);
    }

    public function edit(VendorPayment $vendorPayment, EnsureVendorPaymentInBranchAction $ensureVendorPaymentInBranchAction): View
    {
        $vendorPayment = $ensureVendorPaymentInBranchAction->handle($vendorPayment, $this->currentBranchId());

        return view('tenants.vendor-payments.edit', array_merge(
            ['vendorPayment' => $vendorPayment],
            $this->formOptions()
        ));
    }

    public function update(
        VendorPaymentRequest $request,
        VendorPayment $vendorPayment,
        UpdateVendorPaymentAction $action,
        EnsureVendorPaymentInBranchAction $ensureVendorPaymentInBranchAction
    ): RedirectResponse {
        $vendorPayment = $ensureVendorPaymentInBranchAction->handle($vendorPayment, $this->currentBranchId());
        $action->handle($vendorPayment, $request->validated(), $this->currentBranchId(), Auth::guard('user')->user());

        return to_route('tenant.vendor-payments.index')->with('status', 'Updated.');
    }

    public function destroy(
        VendorPayment $vendorPayment,
        DeleteVendorPaymentAction $action,
        EnsureVendorPaymentInBranchAction $ensureVendorPaymentInBranchAction
    ): RedirectResponse {
        $vendorPayment = $ensureVendorPaymentInBranchAction->handle($vendorPayment, $this->currentBranchId());
        $action->handle($vendorPayment, Auth::guard('user')->user());

        return to_route('tenant.vendor-payments.index')->with('status', 'Deleted.');
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

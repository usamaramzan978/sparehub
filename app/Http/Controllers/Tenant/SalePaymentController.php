<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\PaymentMethodType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\SalePaymentRequest;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class SalePaymentController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $perPage = min(max($request->integer('per_page', 15), 5), 100);

        $payments = SalePayment::query()
            ->with(['sale', 'receiver'])
            ->where('branch_id', $branchId)
            ->latest('paid_at')
            ->paginate($perPage);

        return view('tenants.sale-payments.index', [
            'items' => $payments,
        ]);
    }

    public function create(): View
    {
        return view('tenants.sale-payments.create', $this->formOptions());
    }

    public function store(SalePaymentRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $payload['branch_id'] = $this->currentBranchId();

        $payment = SalePayment::query()->create($payload);
        $this->recalculatePaid($payment->sale);

        return to_route('tenant.sale-payments.index')->with('status', 'Created.');
    }

    public function show(SalePayment $salePayment): View
    {
        $this->ensureSalePaymentInCurrentBranch($salePayment);

        $salePayment->load(['sale.customer', 'receiver', 'branch']);

        return view('tenants.sale-payments.show', [
            'salePayment' => $salePayment,
        ]);
    }

    public function edit(SalePayment $salePayment): View
    {
        $this->ensureSalePaymentInCurrentBranch($salePayment);

        return view('tenants.sale-payments.edit', array_merge(
            ['salePayment' => $salePayment],
            $this->formOptions()
        ));
    }

    public function update(SalePaymentRequest $request, SalePayment $salePayment): RedirectResponse
    {
        $this->ensureSalePaymentInCurrentBranch($salePayment);

        $payload = $request->validated();
        $payload['branch_id'] = $this->currentBranchId();

        $salePayment->update($payload);
        $this->recalculatePaid($salePayment->sale);

        return to_route('tenant.sale-payments.index')->with('status', 'Updated.');
    }

    public function destroy(SalePayment $salePayment): RedirectResponse
    {
        $this->ensureSalePaymentInCurrentBranch($salePayment);
        $sale = $salePayment->sale;
        $salePayment->delete();
        $this->recalculatePaid($sale);

        return to_route('tenant.sale-payments.index')->with('status', 'Deleted.');
    }

    private function ensureSalePaymentInCurrentBranch(SalePayment $salePayment): void
    {
        abort_if($salePayment->branch_id !== $this->currentBranchId(), 404);
    }

    private function recalculatePaid(?Sale $sale): void
    {
        if (! $sale instanceof Sale) {
            return;
        }

        $paid = (float) $sale->payments()->sum('amount');
        $grandTotal = (float) $sale->grand_total;

        $sale->update([
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
            'sales' => Sale::query()->where('branch_id', $branchId)->latest('invoice_date')->get(),
            'receivers' => User::query()->where('branch_id', $branchId)->orderBy('name')->get(),
            'methods' => PaymentMethodType::cases(),
        ];
    }
}

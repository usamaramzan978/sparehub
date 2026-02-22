<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\PaymentMethodType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\SalePaymentRequest;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\User;
use App\Support\AuditTimelineLogger;
use BackedEnum;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        AuditTimelineLogger::log(
            event: 'sale_payment_recorded',
            description: 'Sale payment recorded.',
            causer: Auth::guard('user')->user(),
            subject: $payment,
            properties: [
                'sale_id' => (string) $payment->sale_id,
                'payment_id' => (string) $payment->id,
                'amount' => (float) $payment->amount,
                'method' => $this->paymentMethodValue($payment->payment_method),
            ],
        );

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

        AuditTimelineLogger::log(
            event: 'sale_payment_updated',
            description: 'Sale payment updated.',
            causer: Auth::guard('user')->user(),
            subject: $salePayment,
            properties: [
                'sale_id' => (string) $salePayment->sale_id,
                'payment_id' => (string) $salePayment->id,
                'amount' => (float) $salePayment->amount,
                'method' => $this->paymentMethodValue($salePayment->payment_method),
            ],
        );

        return to_route('tenant.sale-payments.index')->with('status', 'Updated.');
    }

    public function destroy(SalePayment $salePayment): RedirectResponse
    {
        $this->ensureSalePaymentInCurrentBranch($salePayment);
        $sale = $salePayment->sale;
        $snapshot = [
            'sale_id' => (string) $salePayment->sale_id,
            'payment_id' => (string) $salePayment->id,
            'amount' => (float) $salePayment->amount,
            'method' => $this->paymentMethodValue($salePayment->payment_method),
        ];
        $salePayment->delete();
        $this->recalculatePaid($sale);

        AuditTimelineLogger::log(
            event: 'sale_payment_deleted',
            description: 'Sale payment deleted.',
            causer: Auth::guard('user')->user(),
            subject: $salePayment,
            properties: $snapshot,
        );

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

    private function paymentMethodValue(mixed $method): string
    {
        if ($method instanceof BackedEnum) {
            return (string) $method->value;
        }

        return (string) $method;
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

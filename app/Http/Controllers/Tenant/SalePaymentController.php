<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\SalePayment\CreateSalePaymentAction;
use App\Actions\Tenant\SalePayment\DeleteSalePaymentAction;
use App\Actions\Tenant\SalePayment\EnsureSalePaymentInBranchAction;
use App\Actions\Tenant\SalePayment\UpdateSalePaymentAction;
use App\Enums\PaymentMethodType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\SalePaymentRequest;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class SalePaymentController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $perPage = min(max($request->integer('per_page', 15), 5), 100);
        $search = mb_trim($request->string('search')->toString());

        $payments = SalePayment::query()
            ->with(['sale', 'receiver'])
            ->where('branch_id', $branchId)
            ->when(filled($search), function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->whereHas('sale', fn (Builder $saleQuery) => $saleQuery->where('invoice_no', 'like', sprintf('%%%s%%', $search)))
                        ->orWhereHas('receiver', fn (Builder $receiverQuery) => $receiverQuery->where('name', 'like', sprintf('%%%s%%', $search)))
                        ->orWhere('reference_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('payment_method', 'like', sprintf('%%%s%%', $search));
                });
            })
            ->latest('paid_at')
            ->paginate($perPage)
            ->withQueryString();

        return view('tenants.sale-payments.index', [
            'items' => $payments,
        ]);
    }

    public function create(): View
    {
        return view('tenants.sale-payments.create', $this->formOptions());
    }

    public function store(SalePaymentRequest $request, CreateSalePaymentAction $action): RedirectResponse
    {
        $action->handle($request->validated(), $this->currentBranchId(), Auth::guard('user')->user());

        return to_route('tenant.sale-payments.index')->with('status', 'Created.');
    }

    public function show(SalePayment $salePayment, EnsureSalePaymentInBranchAction $ensureSalePaymentInBranchAction): View
    {
        $salePayment = $ensureSalePaymentInBranchAction->handle($salePayment, $this->currentBranchId());

        $salePayment->load(['sale.customer', 'receiver', 'branch']);

        return view('tenants.sale-payments.show', [
            'salePayment' => $salePayment,
        ]);
    }

    public function edit(SalePayment $salePayment, EnsureSalePaymentInBranchAction $ensureSalePaymentInBranchAction): View
    {
        $salePayment = $ensureSalePaymentInBranchAction->handle($salePayment, $this->currentBranchId());

        return view('tenants.sale-payments.edit', array_merge(
            ['salePayment' => $salePayment],
            $this->formOptions()
        ));
    }

    public function update(
        SalePaymentRequest $request,
        SalePayment $salePayment,
        UpdateSalePaymentAction $action,
        EnsureSalePaymentInBranchAction $ensureSalePaymentInBranchAction
    ): RedirectResponse {
        $salePayment = $ensureSalePaymentInBranchAction->handle($salePayment, $this->currentBranchId());
        $action->handle($salePayment, $request->validated(), $this->currentBranchId(), Auth::guard('user')->user());

        return to_route('tenant.sale-payments.index')->with('status', 'Updated.');
    }

    public function destroy(
        SalePayment $salePayment,
        DeleteSalePaymentAction $action,
        EnsureSalePaymentInBranchAction $ensureSalePaymentInBranchAction
    ): RedirectResponse {
        $salePayment = $ensureSalePaymentInBranchAction->handle($salePayment, $this->currentBranchId());
        $action->handle($salePayment, Auth::guard('user')->user());

        return to_route('tenant.sale-payments.index')->with('status', 'Deleted.');
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

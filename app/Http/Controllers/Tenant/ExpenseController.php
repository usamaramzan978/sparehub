<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\PaymentMethodType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\ExpenseRequest;
use App\Models\Expense;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $perPage = min(max($request->integer('per_page', 15), 5), 100);
        $search = mb_trim($request->string('search')->toString());
        $dateFrom = mb_trim($request->string('date_from')->toString());
        $dateTo = mb_trim($request->string('date_to')->toString());

        $items = Expense::query()
            ->with('creator')
            ->where('branch_id', $branchId)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('title', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('category', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('reference_no', 'like', sprintf('%%%s%%', $search));
                });
            })
            ->when($dateFrom !== '', fn (Builder $query) => $query->whereDate('expense_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn (Builder $query) => $query->whereDate('expense_date', '<=', $dateTo))
            ->latest('expense_date')
            ->paginate($perPage)
            ->withQueryString();

        $summaryQuery = Expense::query()
            ->where('branch_id', $branchId)
            ->when($dateFrom !== '', fn (Builder $query) => $query->whereDate('expense_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn (Builder $query) => $query->whereDate('expense_date', '<=', $dateTo));

        return view('tenants.expenses.index', [
            'items' => $items,
            'methods' => PaymentMethodType::cases(),
            'summary' => [
                'count' => (clone $summaryQuery)->count(),
                'total' => (float) (clone $summaryQuery)->sum('amount'),
            ],
        ]);
    }

    public function store(ExpenseRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $payload['branch_id'] = $this->currentBranchId();
        $payload['created_by'] = auth('user')->id();

        Expense::query()->create($payload);

        return to_route('tenant.expenses.index')->with('status', 'Created.');
    }

    public function update(ExpenseRequest $request, string $tenant, string $expense): RedirectResponse
    {
        unset($tenant);
        $expense = $this->resolveExpense($expense);
        $this->ensureExpenseInCurrentBranch($expense);
        $expense->update($request->validated());

        return to_route('tenant.expenses.index')->with('status', 'Updated.');
    }

    public function destroy(string $tenant, string $expense): RedirectResponse
    {
        unset($tenant);
        $expense = $this->resolveExpense($expense);
        $this->ensureExpenseInCurrentBranch($expense);
        $expense->delete();

        return to_route('tenant.expenses.index')->with('status', 'Deleted.');
    }

    private function ensureExpenseInCurrentBranch(Expense $expense): void
    {
        abort_if($expense->branch_id !== $this->currentBranchId(), 404);
    }

    private function resolveExpense(string $expenseId): Expense
    {
        return Expense::query()->withoutGlobalScopes()->findOrFail($expenseId);
    }
}

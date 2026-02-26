<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\Expense\CreateExpenseAction;
use App\Actions\Tenant\Expense\DeleteExpenseAction;
use App\Actions\Tenant\Expense\UpdateExpenseAction;
use App\Enums\PaymentMethodType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\ExpenseRequest;
use App\Models\Expense;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
            ->when(filled($search), function (Builder $query) use ($search): void {
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

    public function store(ExpenseRequest $request, CreateExpenseAction $action): RedirectResponse
    {
        $action->handle($request->validated(), $this->currentBranchId(), auth('user')->id());

        return to_route('tenant.expenses.index')->with('status', 'Created.');
    }

    public function update(ExpenseRequest $request, UpdateExpenseAction $action): RedirectResponse
    {
        $action->handle($this->resolveExpenseId($request), $request->validated(), $this->currentBranchId());

        return to_route('tenant.expenses.index')->with('status', 'Updated.');
    }

    public function destroy(Request $request, DeleteExpenseAction $action): RedirectResponse
    {
        $action->handle($this->resolveExpenseId($request), $this->currentBranchId());

        return to_route('tenant.expenses.index')->with('status', 'Deleted.');
    }

    private function resolveExpenseId(Request $request): string
    {
        $expense = $request->route('expense');

        if ($expense instanceof Expense) {
            return (string) $expense->id;
        }

        if (! is_string($expense) || $expense === '') {
            throw new NotFoundHttpException();
        }

        return $expense;
    }
}

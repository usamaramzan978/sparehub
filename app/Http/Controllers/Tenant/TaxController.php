<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\Tax\CreateTaxAction;
use App\Actions\Tenant\Tax\DeleteTaxAction;
use App\Actions\Tenant\Tax\UpdateTaxAction;
use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\TaxRequest;
use App\Models\Tax;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class TaxController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = min(max($request->integer('per_page', 15), 5), 100);
        $search = mb_trim($request->string('search')->toString());
        $sortBy = $request->string('sort_by')->toString();
        $sortDirection = $request->string('sort_direction')->toString();
        $allowedSortColumns = ['code', 'name', 'rate', 'status', 'created_at'];
        $activeSortBy = in_array($sortBy, $allowedSortColumns, true) ? $sortBy : null;
        $activeSortDirection = in_array($sortDirection, ['asc', 'desc'], true) ? $sortDirection : 'asc';

        $taxesQuery = Tax::query()
            ->when(filled($search), function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('code', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('name', 'like', sprintf('%%%s%%', $search));
                });
            });

        if ($activeSortBy !== null) {
            $taxesQuery->orderBy($activeSortBy, $activeSortDirection);
        } else {
            $taxesQuery->latest();
        }

        $taxes = $taxesQuery
            ->paginate($perPage)
            ->withQueryString();

        $statuses = RecordStatus::cases();

        return view('tenants.taxes.index', [
            'items' => $taxes,
            'statuses' => $statuses,
            'sortBy' => $activeSortBy,
            'sortDirection' => $activeSortDirection,
        ]);
    }

    public function store(TaxRequest $request, CreateTaxAction $action): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_inclusive'] = $request->boolean('is_inclusive');
        $action->handle($validated);

        return to_route('tenant.taxes.index')
            ->with('status', 'Created.');
    }

    public function update(TaxRequest $request, Tax $tax, UpdateTaxAction $action): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_inclusive'] = $request->boolean('is_inclusive');
        $action->handle($tax, $validated);

        return to_route('tenant.taxes.index')
            ->with('status', 'Updated.');
    }

    public function destroy(Tax $tax, DeleteTaxAction $action): RedirectResponse
    {
        $action->handle($tax);

        return to_route('tenant.taxes.index')
            ->with('status', 'Deleted.');
    }
}

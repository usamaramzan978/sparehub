<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\Unit\CreateUnitAction;
use App\Actions\Tenant\Unit\DeleteUnitAction;
use App\Actions\Tenant\Unit\UpdateUnitAction;
use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UnitRequest;
use App\Models\Unit;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class UnitController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = min(max($request->integer('per_page', 15), 5), 100);
        $search = mb_trim($request->string('search')->toString());
        $sortBy = $request->string('sort_by')->toString();
        $sortDirection = $request->string('sort_direction')->toString();
        $allowedSortColumns = ['code', 'name', 'is_fractional', 'status', 'created_at'];
        $activeSortBy = in_array($sortBy, $allowedSortColumns, true) ? $sortBy : null;
        $activeSortDirection = in_array($sortDirection, ['asc', 'desc'], true) ? $sortDirection : 'asc';

        $unitsQuery = Unit::query()
            ->when(filled($search), function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('code', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('name', 'like', sprintf('%%%s%%', $search));
                });
            });

        if ($activeSortBy !== null) {
            $unitsQuery->orderBy($activeSortBy, $activeSortDirection);
        } else {
            $unitsQuery->latest();
        }

        $units = $unitsQuery
            ->paginate($perPage)
            ->withQueryString();
        $statuses = RecordStatus::cases();

        return view('tenants.units.index', [
            'items' => $units,
            'statuses' => $statuses,
            'sortBy' => $activeSortBy,
            'sortDirection' => $activeSortDirection,
        ]);
    }

    public function store(UnitRequest $request, CreateUnitAction $action): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_fractional'] = $request->boolean('is_fractional');
        $action->handle($validated);

        return to_route('tenant.units.index')
            ->with('status', 'Created.');
    }

    public function update(UnitRequest $request, Unit $unit, UpdateUnitAction $action): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_fractional'] = $request->boolean('is_fractional');
        $action->handle($unit, $validated);

        return to_route('tenant.units.index')
            ->with('status', 'Updated.');
    }

    public function destroy(Unit $unit, DeleteUnitAction $action): RedirectResponse
    {
        $action->handle($unit);

        return to_route('tenant.units.index')
            ->with('status', 'Deleted.');
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\Warehouse\CreateWarehouseAction;
use App\Actions\Tenant\Warehouse\DeleteWarehouseAction;
use App\Actions\Tenant\Warehouse\UpdateWarehouseAction;
use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\WarehouseRequest;
use App\Models\Warehouse;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class WarehouseController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = min(max($request->integer('per_page', 15), 5), 100);
        $search = mb_trim($request->string('search')->toString());
        $sortBy = $request->string('sort_by')->toString();
        $sortDirection = $request->string('sort_direction')->toString();
        $allowedSortColumns = ['code', 'name', 'status', 'created_at'];
        $activeSortBy = in_array($sortBy, $allowedSortColumns, true) ? $sortBy : null;
        $activeSortDirection = in_array($sortDirection, ['asc', 'desc'], true) ? $sortDirection : 'asc';

        $warehousesQuery = Warehouse::query()
            ->with('branch')
            ->when(filled($search), function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('code', 'like', sprintf('%%%s%%', $search));
                });
            });

        if ($activeSortBy !== null) {
            $warehousesQuery->orderBy($activeSortBy, $activeSortDirection);
        } else {
            $warehousesQuery->latest();
        }

        $warehouses = $warehousesQuery
            ->paginate($perPage)
            ->withQueryString();

        return view('tenants.warehouses.index', [
            'items' => $warehouses,
            'statuses' => RecordStatus::cases(),
            'sortBy' => $activeSortBy,
            'sortDirection' => $activeSortDirection,
        ]);
    }

    public function store(WarehouseRequest $request, CreateWarehouseAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('tenant.warehouses.index')
            ->with('status', 'Created.');
    }

    public function update(WarehouseRequest $request, Warehouse $warehouse, UpdateWarehouseAction $action): RedirectResponse
    {
        $action->handle($warehouse, $request->validated());

        return to_route('tenant.warehouses.index')
            ->with('status', 'Updated.');
    }

    public function destroy(Warehouse $warehouse, DeleteWarehouseAction $action): RedirectResponse
    {
        $action->handle($warehouse);

        return to_route('tenant.warehouses.index')
            ->with('status', 'Deleted.');
    }
}

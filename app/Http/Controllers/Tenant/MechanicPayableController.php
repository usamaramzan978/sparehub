<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class MechanicPayableController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->currentBranchId();
        $search = mb_trim($request->string('search')->toString());
        $mechanicId = mb_trim($request->string('mechanic_id')->toString());
        $dateFrom = mb_trim($request->string('date_from')->toString());
        $dateTo = mb_trim($request->string('date_to')->toString());
        $perPage = min(max($request->integer('per_page', 20), 5), 100);

        $baseQuery = SaleItem::query()
            ->with(['sale', 'mechanic', 'serviceCatalog'])
            ->where('branch_id', $branchId)
            ->where('line_type', 'service')
            ->whereNotNull('mechanic_id')
            ->where('mechanic_charge', '>', 0)
            ->whereHas('sale')
            ->when($mechanicId !== '', fn (Builder $query) => $query->where('mechanic_id', $mechanicId))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('description', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('sale', fn (Builder $saleQuery) => $saleQuery->where('invoice_no', 'like', sprintf('%%%s%%', $search)))
                        ->orWhereHas('mechanic', fn (Builder $mechanicQuery) => $mechanicQuery->where('name', 'like', sprintf('%%%s%%', $search)))
                        ->orWhereHas('serviceCatalog', fn (Builder $serviceQuery) => $serviceQuery->where('name', 'like', sprintf('%%%s%%', $search)));
                });
            })
            ->when($dateFrom !== '', fn (Builder $query) => $query->whereHas('sale', fn (Builder $saleQuery) => $saleQuery->whereDate('invoice_date', '>=', $dateFrom)))
            ->when($dateTo !== '', fn (Builder $query) => $query->whereHas('sale', fn (Builder $saleQuery) => $saleQuery->whereDate('invoice_date', '<=', $dateTo)));

        $items = (clone $baseQuery)
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();

        $summary = [
            'lines_count' => (clone $baseQuery)->count(),
            'total_payable' => (float) (clone $baseQuery)->sum('mechanic_charge'),
        ];

        $byMechanicRows = (clone $baseQuery)
            ->selectRaw('mechanic_id, SUM(mechanic_charge) as total_payable, COUNT(*) as lines_count')
            ->groupBy('mechanic_id')
            ->orderByDesc('total_payable')
            ->limit(20)
            ->toBase()
            ->get();

        $mechanicNames = User::query()
            ->whereIn('id', collect($byMechanicRows)->pluck('mechanic_id')->all())
            ->pluck('name', 'id');

        $byMechanic = collect($byMechanicRows)->map(function (object $row) use ($mechanicNames): array {
            $mechanicName = $mechanicNames[$row->mechanic_id] ?? null;

            return [
                'mechanic_name' => is_string($mechanicName) ? $mechanicName : 'Unknown Mechanic',
                'total_payable' => (float) $row->total_payable,
                'lines_count' => (int) $row->lines_count,
            ];
        });

        return view('tenants.mechanic-payables.index', [
            'items' => $items,
            'summary' => $summary,
            'byMechanic' => $byMechanic,
            'mechanics' => User::query()
                ->where('branch_id', $branchId)
                ->active()
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }
}

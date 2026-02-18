<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UnitRequest;
use App\Models\Unit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class UnitController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = min(max($request->integer('per_page', 15), 5), 100);

        $units = Unit::query()
            ->latest()
            ->paginate($perPage);
        $statuses = RecordStatus::cases();

        return view('tenants.units.index', [
            'items' => $units,
            'statuses' => $statuses,
        ]);
    }

    public function store(UnitRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_fractional'] = $request->boolean('is_fractional');
        Unit::query()->create($validated);

        return to_route('tenant.units.index')
            ->with('status', 'Created.');
    }

    public function update(UnitRequest $request, Unit $unit): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_fractional'] = $request->boolean('is_fractional');
        $unit->update($validated);

        return to_route('tenant.units.index')
            ->with('status', 'Updated.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $unit->delete();

        return to_route('tenant.units.index')
            ->with('status', 'Deleted.');
    }
}

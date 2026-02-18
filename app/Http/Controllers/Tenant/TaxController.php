<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\TaxRequest;
use App\Models\Tax;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class TaxController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = min(max($request->integer('per_page', 15), 5), 100);

        $taxes = Tax::query()
            ->latest()
            ->paginate($perPage);

        $statuses = RecordStatus::cases();

        return view('tenants.taxes.index', [
            'items' => $taxes,
            'statuses' => $statuses,
        ]);
    }

    public function store(TaxRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_inclusive'] = $request->boolean('is_inclusive');
        Tax::query()->create();

        return to_route('tenant.taxes.index')
            ->with('status', 'Created.');
    }

    public function update(TaxRequest $request, Tax $tax): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_inclusive'] = $request->boolean('is_inclusive');
        $tax->update($validated);

        return to_route('tenant.taxes.index')
            ->with('status', 'Updated.');
    }

    public function destroy(Tax $tax): RedirectResponse
    {
        $tax->delete();

        return to_route('tenant.taxes.index')
            ->with('status', 'Deleted.');
    }
}

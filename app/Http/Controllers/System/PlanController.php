<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\System\PlanRequest;
use App\Models\Plan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class PlanController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = min(max($request->integer('per_page', 15), 5), 100);

        $plans = Plan::query()
            ->withCount('tenants')
            ->latest()
            ->paginate($perPage);

        return view('system.plans.index', ['plans' => $plans]);
    }

    public function create(): View
    {
        return view('system.plans.create', ['statuses' => RecordStatus::cases()]);
    }

    public function store(PlanRequest $request): RedirectResponse
    {
        Plan::query()->create($request->validated());

        return to_route('system.plans.index')->with('status', 'Plan created successfully.');
    }

    public function edit(Plan $plan): View
    {
        return view('system.plans.edit', [
            'plan' => $plan,
            'statuses' => RecordStatus::cases(),
        ]);
    }

    public function update(PlanRequest $request, Plan $plan): RedirectResponse
    {
        $plan->update($request->validated());

        return to_route('system.plans.index')->with('status', 'Plan updated successfully.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        if ($plan->tenants()->exists()) {
            return to_route('system.plans.index')->with('error', 'This plan is assigned to one or more tenants.');
        }

        $plan->delete();

        return to_route('system.plans.index')->with('status', 'Plan deleted successfully.');
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\BranchStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\BranchRequest;
use App\Models\Branch;
use App\Models\Warehouse;
use App\Support\AuditTimelineLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class BranchController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = $request->integer('per_page', 15);
        $perPage = min(max($perPage, 5), 100);

        $branches = Branch::query()
            ->with('warehouse')
            ->latest()
            ->paginate($perPage);

        return view('tenants.branches.index', ['items' => $branches]);
    }

    public function store(BranchRequest $request): RedirectResponse
    {
        $branch = Branch::query()->create($request->validated());

        AuditTimelineLogger::log(
            event: 'branch_created',
            description: 'Branch created.',
            causer: Auth::guard('user')->user(),
            subject: $branch,
            properties: [
                'branch_id' => (string) $branch->id,
                'branch_name' => $branch->name,
            ],
        );

        return to_route('tenant.branches.index')
            ->with('status', 'Created.');
    }

    public function create(): View
    {
        $statuses = BranchStatus::cases();
        $warehouses = Warehouse::query()->orderBy('name')->get();

        return view('tenants.branches.create', [
            'statuses' => $statuses,
            'warehouses' => $warehouses,
        ]);
    }

    public function show(Branch $branch): View
    {
        $branch->load(['warehouse']);

        return view('tenants.branches.show', ['branch' => $branch]);
    }

    public function update(BranchRequest $request, Branch $branch): RedirectResponse
    {
        $changes = $request->validated();
        $branch->update($changes);

        AuditTimelineLogger::log(
            event: 'branch_updated',
            description: 'Branch updated.',
            causer: Auth::guard('user')->user(),
            subject: $branch,
            properties: [
                'branch_id' => (string) $branch->id,
                'branch_name' => $branch->name,
                'changed_attributes' => array_keys($changes),
            ],
        );

        return to_route('tenant.branches.index')
            ->with('status', 'Updated.');
    }

    public function edit(Branch $branch): View
    {
        $statuses = BranchStatus::cases();
        $warehouses = Warehouse::query()->orderBy('name')->get();

        return view('tenants.branches.edit', [
            'branch' => $branch,
            'statuses' => $statuses,
            'warehouses' => $warehouses,
        ]);
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        if (Branch::query()->count() <= 1) {
            return to_route('tenant.branches.index')
                ->with('error', 'At least one branch must remain.');
        }

        if (session('tenant.current_branch_id') === $branch->id) {
            return to_route('tenant.branches.index')
                ->with('error', 'You cannot delete the currently selected branch.');
        }

        $branchSnapshot = [
            'branch_id' => (string) $branch->id,
            'branch_name' => $branch->name,
        ];

        $branch->delete();

        AuditTimelineLogger::log(
            event: 'branch_deleted',
            description: 'Branch deleted.',
            causer: Auth::guard('user')->user(),
            subject: $branch,
            properties: $branchSnapshot,
        );

        return to_route('tenant.branches.index')
            ->with('status', 'Deleted.');
    }
}

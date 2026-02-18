<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\BranchSwitchRequest;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;

final class BranchSwitchController extends Controller
{
    public function store(BranchSwitchRequest $request): RedirectResponse
    {
        $payload = $request->validated();

        $branch = Branch::query()
            ->active()
            ->whereKey($payload['branch_id'])
            ->first();

        if (! $branch) {
            return back()->with('error', 'Selected branch is not active.');
        }

        $request->session()->put('tenant.current_branch_id', $branch->id);

        return back()->with('status', 'Branch switched.');
    }
}

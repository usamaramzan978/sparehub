<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class BranchSwitchController extends Controller
{
    public function store(Request $request): RedirectResponse
    {

        $payload = $request->validate([
            'branch_id' => ['required', 'uuid', 'exists:branches,id'],
        ]);

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

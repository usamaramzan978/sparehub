<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\BranchSwitchRequest;
use App\Models\Branch;
use App\Support\AuditTimelineLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

final class BranchSwitchController extends Controller
{
    public function store(BranchSwitchRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $previousBranchId = (string) $request->session()->get('tenant.current_branch_id', '');

        $branch = Branch::query()
            ->active()
            ->whereKey($payload['branch_id'])
            ->first();

        if (! $branch) {
            return back()->with('error', 'Selected branch is not active.');
        }

        $request->session()->put('tenant.current_branch_id', $branch->id);

        AuditTimelineLogger::log(
            event: 'branch_switched',
            description: 'Branch context switched.',
            causer: Auth::guard('user')->user(),
            subject: $branch,
            properties: [
                'from_branch_id' => $previousBranchId !== '' ? $previousBranchId : null,
                'to_branch_id' => (string) $branch->id,
                'to_branch_name' => $branch->name,
            ],
        );

        return back()->with('status', 'Branch switched.');
    }
}

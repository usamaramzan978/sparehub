<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\SystemUser;
use App\Models\Tenant;
use Illuminate\Contracts\View\View;

final class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $summary = [
            'system_users' => SystemUser::query()->count(),
            'tenants_total' => Tenant::query()->count(),
            'tenants_active' => Tenant::query()->active()->count(),
            'plans_total' => Plan::query()->count(),
        ];

        $recentTenants = Tenant::query()
            ->with(['plan'])
            ->latest()
            ->limit(8)
            ->get();

        $recentSystemUsers = SystemUser::query()
            ->latest()
            ->limit(8)
            ->get();

        return view('system.dashboard', [
            'summary' => $summary,
            'recentTenants' => $recentTenants,
            'recentSystemUsers' => $recentSystemUsers,
        ]);
    }
}

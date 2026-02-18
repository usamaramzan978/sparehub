<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Enums\LoginUserType;
use App\Enums\TenantStatus;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\System\TenantRequest;
use App\Models\Branch;
use App\Models\LoginMap;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class TenantController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = min(max($request->integer('per_page', 15), 5), 100);
        $search = $request->string('search')->toString();

        $tenants = Tenant::query()
            ->with(['plan'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($tenantQuery) use ($search): void {
                    $tenantQuery->where('name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('slug', 'like', sprintf('%%%s%%', $search));
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('system.tenants.index', [
            'tenants' => $tenants,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('system.tenants.create', [
            'plans' => Plan::query()->where('status', 'active')->orderBy('name')->get(),
            'statuses' => TenantStatus::cases(),
        ]);
    }

    public function store(TenantRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $tenant = Tenant::query()->create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'status' => $validated['status'],
            'plan_id' => $validated['plan_id'] ?? null,
            'data' => [
                'owner_name' => $validated['owner_name'],
                'owner_email' => mb_strtolower((string) $validated['owner_email']),
                'owner_phone' => $validated['owner_phone'] ?? null,
            ],
        ]);

        $this->createTenantOwnerUser($tenant, $validated);

        return to_route('system.tenants.index')->with('status', 'Tenant and owner user created successfully. The owner can now log in from tenant login.');
    }

    public function edit(Tenant $tenant): View
    {
        return view('system.tenants.edit', [
            'tenant' => $tenant->load(['plan']),
            'plans' => Plan::query()->where('status', 'active')->orderBy('name')->get(),
            'statuses' => TenantStatus::cases(),
        ]);
    }

    private function createTenantOwnerUser(Tenant $tenant, array $validated): void
    {
        $ownerEmail = mb_strtolower((string) $validated['owner_email']);
        $ownerPasswordHash = Hash::make((string) $validated['owner_password']);

        $ownerUserId = $tenant->run(function () use ($validated, $ownerEmail, $ownerPasswordHash): ?string {

            $branch = Branch::query()->orderByDesc('is_default')->oldest()->first();

            if (! $branch) {
                $branch = Branch::query()->create([
                    'code' => 'MAIN',
                    'name' => 'Main Branch',
                    'status' => 'active',
                    'is_default' => true,
                ]);
            }

            $user = User::query()->create([
                'branch_id' => $branch->id,
                'name' => $validated['owner_name'],
                'email' => $ownerEmail,
                'phone' => $validated['owner_phone'] ?? null,
                'password' => $ownerPasswordHash,
                'status' => UserStatus::ACTIVE->value,
                'email_verified_at' => now(),
            ]);

            return $user->id;
        });

        if (! $ownerUserId) {
            return;
        }

        LoginMap::query()->updateOrCreate(
            [
                'tenant_id' => (string) $tenant->id,
                'type' => LoginUserType::USER->value,
                'type_id' => $ownerUserId,
            ],
            [
                'email' => $ownerEmail,
                'password' => $ownerPasswordHash,
                'status' => true,
            ]
        );
    }
}

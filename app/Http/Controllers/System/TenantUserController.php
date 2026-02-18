<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Enums\LoginUserType;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\System\TenantUserRequest;
use App\Models\Branch;
use App\Models\LoginMap;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class TenantUserController extends Controller
{
    public function index(Request $request): View
    {
        $tenants = Tenant::query()->orderBy('name')->get(['id', 'name', 'slug', 'status']);
        $tenantId = mb_trim($request->string('tenant_id')->toString());
        $selectedTenant = $tenantId !== '' ? Tenant::query()->find($tenantId) : null;

        $tenantUsers = collect();
        if ($selectedTenant instanceof Tenant) {
            /** @var Collection<int, array{id:string,name:string,email:string,phone:?string,status:string,branch:?string,created_at:string}> $tenantUsers */
            $tenantUsers = $selectedTenant->run(function () {
                return User::query()
                    ->with('branch:id,name')
                    ->latest()
                    ->get()
                    ->map(function (User $user): array {
                        $status = $user->status instanceof UserStatus ? $user->status->value : (string) $user->status;

                        return [
                            'id' => (string) $user->id,
                            'name' => (string) $user->name,
                            'email' => (string) $user->email,
                            'phone' => $user->phone,
                            'status' => $status,
                            'branch' => $user->branch?->name,
                            'created_at' => $user->created_at?->format('Y-m-d H:i') ?? '-',
                        ];
                    });
            });
        }

        return view('system.tenant-users.index', [
            'tenants' => $tenants,
            'selectedTenant' => $selectedTenant,
            'tenantUsers' => $tenantUsers,
        ]);
    }

    public function create(Request $request): View
    {
        $tenants = Tenant::query()->orderBy('name')->get(['id', 'name', 'slug', 'status']);
        $selectedTenantId = mb_trim($request->string('tenant_id')->toString());

        return view('system.tenant-users.create', [
            'tenants' => $tenants,
            'statuses' => UserStatus::cases(),
            'selectedTenantId' => $selectedTenantId,
        ]);
    }

    public function store(TenantUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $tenant = Tenant::query()->findOrFail($validated['tenant_id']);
        $email = mb_strtolower((string) $validated['email']);
        $passwordHash = Hash::make((string) $validated['password']);
        $statusValue = $validated['status'] instanceof UserStatus
            ? $validated['status']->value
            : (string) $validated['status'];

        $createdUserId = $tenant->run(function () use ($validated, $email, $passwordHash, $statusValue): string {
            if (User::query()->where('email', $email)->exists()) {
                throw ValidationException::withMessages([
                    'email' => 'This email is already in use for the selected tenant.',
                ]);
            }

            $branch = Branch::query()->orderByDesc('is_default')->orderBy('created_at')->first();

            if (! $branch instanceof Branch) {
                $branch = Branch::query()->create([
                    'code' => 'MAIN',
                    'name' => 'Main Branch',
                    'status' => 'active',
                    'is_default' => true,
                ]);
            }

            $user = User::query()->create([
                'branch_id' => $branch->id,
                'name' => $validated['name'],
                'email' => $email,
                'phone' => $validated['phone'] ?? null,
                'password' => $passwordHash,
                'status' => $statusValue,
                'email_verified_at' => now(),
            ]);

            return (string) $user->id;
        });

        LoginMap::query()->updateOrCreate(
            [
                'tenant_id' => (string) $tenant->id,
                'type' => LoginUserType::USER->value,
                'type_id' => $createdUserId,
            ],
            [
                'email' => $email,
                'password' => $passwordHash,
                'status' => $statusValue === UserStatus::ACTIVE->value,
            ]
        );

        return to_route('system.tenant-users.index', ['tenant_id' => $tenant->id])
            ->with('status', 'Tenant user created successfully.');
    }
}

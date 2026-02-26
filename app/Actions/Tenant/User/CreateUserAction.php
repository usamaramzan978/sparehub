<?php

declare(strict_types=1);

namespace App\Actions\Tenant\User;

use App\Enums\LoginUserType;
use App\Enums\UserStatus;
use App\Models\LoginMap;
use App\Models\User;
use App\Support\AuditTimelineLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class CreateUserAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data, string $branchId): User
    {
        $maxUsers = (int) config('tenancy.limits.max_users', 10);

        if ($maxUsers > 0 && User::query()->count() >= $maxUsers) {
            throw ValidationException::withMessages([
                'email' => [sprintf('Maximum %d users are allowed for this tenant.', $maxUsers)],
            ]);
        }

        $data['branch_id'] = $branchId;
        $data['email'] = mb_strtolower((string) $data['email']);
        $data['password'] = Hash::make((string) $data['password']);

        $user = User::query()->create($data);

        $this->syncLoginMap($user);

        AuditTimelineLogger::log(
            event: 'user_created',
            description: 'Tenant user created.',
            causer: Auth::guard('user')->user(),
            subject: $user,
            properties: [
                'user_id' => (string) $user->id,
                'user_email' => $user->email,
                'branch_id' => (string) $user->branch_id,
            ],
        );

        return $user;
    }

    private function syncLoginMap(User $user): void
    {
        $tenantId = (string) tenant('id');
        $userStatus = $user->status instanceof UserStatus ? $user->status->value : (string) $user->status;

        throw_if($tenantId === '', HttpException::class, 422, 'Invalid tenant context.');

        LoginMap::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'type' => LoginUserType::USER->value,
                'type_id' => $user->id,
            ],
            [
                'email' => mb_strtolower($user->email),
                'password' => $user->password,
                'status' => $userStatus === UserStatus::ACTIVE->value,
            ]
        );
    }
}

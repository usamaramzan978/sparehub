<?php

declare(strict_types=1);

namespace App\Actions\Tenant\User;

use App\Enums\LoginUserType;
use App\Enums\UserStatus;
use App\Models\LoginMap;
use App\Models\User;
use App\Support\AuditTimelineLogger;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class UpdateUserAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(User $user, array $data, string $branchId): bool
    {
        $data['branch_id'] = $branchId;
        $data['email'] = mb_strtolower((string) $data['email']);

        if (empty($data['password'])) {
            $data = Arr::except($data, ['password']);
        } else {
            $data['password'] = Hash::make((string) $data['password']);
        }

        $updated = $user->update($data);

        $this->syncLoginMap($user->refresh());

        AuditTimelineLogger::log(
            event: 'user_updated',
            description: 'Tenant user updated.',
            causer: Auth::guard('user')->user(),
            subject: $user,
            properties: [
                'user_id' => (string) $user->id,
                'user_email' => $user->email,
                'changed_attributes' => array_keys($data),
            ],
        );

        return $updated;
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

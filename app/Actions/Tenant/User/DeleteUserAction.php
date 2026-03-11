<?php

declare(strict_types=1);

namespace App\Actions\Tenant\User;

use App\Enums\LoginUserType;
use App\Enums\RoleName;
use App\Enums\UserDeletionResult;
use App\Models\LoginMap;
use App\Models\User;
use App\Support\AuditTimelineLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

final class DeleteUserAction
{
    public function handle(User $user): UserDeletionResult
    {
        if ($this->isLastTenantOwner($user)) {
            return UserDeletionResult::LastTenantOwner;
        }

        $userSnapshot = [
            'user_id' => (string) $user->id,
            'user_email' => $user->email,
            'branch_id' => (string) $user->branch_id,
        ];

        $this->deleteLoginMap($user);
        $this->deleteImage($user);

        $user->delete();

        AuditTimelineLogger::log(
            event: 'user_deleted',
            description: 'Tenant user deleted.',
            causer: Auth::guard('user')->user(),
            subject: $user,
            properties: $userSnapshot,
        );

        return UserDeletionResult::Deleted;
    }

    private function deleteLoginMap(User $user): void
    {
        $tenantId = (string) tenant('id');

        if ($tenantId === '') {
            return;
        }

        LoginMap::query()
            ->where('tenant_id', $tenantId)
            ->where('type', LoginUserType::USER->value)
            ->where('type_id', $user->id)
            ->delete();
    }

    private function isLastTenantOwner(User $user): bool
    {
        $isTenantOwner = $user->roles()
            ->where('name', RoleName::TENANT_OWNER->value)
            ->exists();

        if (! $isTenantOwner) {
            return false;
        }

        $tenantOwnersCount = User::query()
            ->whereHas('roles', function (Builder $query): void {
                $query->where('name', RoleName::TENANT_OWNER->value);
            })
            ->count();

        return $tenantOwnersCount <= 1;
    }

    private function deleteImage(User $user): void
    {
        if (blank($user->image_path)) {
            return;
        }

        Storage::disk('public')->delete((string) $user->image_path);
    }
}

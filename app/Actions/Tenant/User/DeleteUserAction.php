<?php

declare(strict_types=1);

namespace App\Actions\Tenant\User;

use App\Enums\LoginUserType;
use App\Models\LoginMap;
use App\Models\User;
use App\Support\AuditTimelineLogger;
use Illuminate\Support\Facades\Auth;

final class DeleteUserAction
{
    public function handle(User $user): bool
    {
        $userSnapshot = [
            'user_id' => (string) $user->id,
            'user_email' => $user->email,
            'branch_id' => (string) $user->branch_id,
        ];

        $this->deleteLoginMap($user);

        $deleted = (bool) $user->delete();

        AuditTimelineLogger::log(
            event: 'user_deleted',
            description: 'Tenant user deleted.',
            causer: Auth::guard('user')->user(),
            subject: $user,
            properties: $userSnapshot,
        );

        return $deleted;
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
}

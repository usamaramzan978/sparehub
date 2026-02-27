<?php

declare(strict_types=1);

namespace App\Actions\Tenant\User;

use App\Enums\LoginUserType;
use App\Enums\UserStatus;
use App\Models\LoginMap;
use App\Models\User;
use App\Support\AuditTimelineLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

final class UpdateUserAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(
        User $user,
        array $data,
        string $branchId,
        SyncUserCommissionRulesAction $syncUserCommissionRulesAction
    ): bool {
        $data['branch_id'] = $branchId;
        $data['email'] = mb_strtolower((string) $data['email']);
        $image = Arr::pull($data, 'image');
        $commissionRules = Arr::pull($data, 'commission_rules', []);

        if (empty($data['password'])) {
            $data = Arr::except($data, ['password']);
        } else {
            $data['password'] = Hash::make((string) $data['password']);
        }

        if ($image instanceof UploadedFile) {
            if (filled($user->image_path)) {
                Storage::disk('public')->delete((string) $user->image_path);
            }

            $data['image_path'] = (string) $image->store('users', 'public');
        }

        $updated = $user->update($data);
        $syncUserCommissionRulesAction->handle($user, is_array($commissionRules) ? $commissionRules : []);

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

        if ($tenantId === '') {
            return;
        }

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

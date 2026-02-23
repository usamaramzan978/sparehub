<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Profile;

use App\Models\User;
use App\Support\AuditTimelineLogger;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;

final class UpdateProfileAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(User $user, array $data): bool
    {
        $data['email'] = mb_strtolower((string) $data['email']);

        if (empty($data['password'])) {
            $data = Arr::except($data, ['password']);
        } else {
            $data['password'] = Hash::make((string) $data['password']);
        }

        $updated = $user->update($data);

        AuditTimelineLogger::log(
            event: 'profile_updated',
            description: 'Profile updated.',
            causer: $user,
            subject: $user,
            properties: [
                'changed_attributes' => array_keys($data),
            ],
        );

        return $updated;
    }
}

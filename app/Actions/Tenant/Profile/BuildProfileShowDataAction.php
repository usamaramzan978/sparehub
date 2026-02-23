<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Profile;

use App\Models\LoginAttempt;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

final class BuildProfileShowDataAction
{
    /**
     * @return array{user: User, stats: array{opened_sessions: int, closed_sessions: int, price_changes: int}}
     */
    public function handle(User $user): array
    {
        $user->load('branch');

        $openedSessions = 0;
        if (Schema::connection('tenant')->hasTable('login_attempts')) {
            $openedSessions = LoginAttempt::query()
                ->where('user_id', $user->id)
                ->where('status', 'success')
                ->count();
        }

        return [
            'user' => $user,
            'stats' => [
                'opened_sessions' => $openedSessions,
                'closed_sessions' => 0,
                'price_changes' => 0,
            ],
        ];
    }
}

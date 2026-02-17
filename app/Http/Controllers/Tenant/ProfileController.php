<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UpdateProfileRequest;
use App\Models\LoginAttempt;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

final class ProfileController extends Controller
{
    public function show(): View
    {
        $user = Auth::guard('user')->user();
        abort_if($user === null, 403);

        $user->load('branch');

        $openedSessions = 0;
        if (Schema::connection('tenant')->hasTable('login_attempts')) {
            $openedSessions = LoginAttempt::query()
                ->where('user_id', $user->id)
                ->where('status', 'success')
                ->count();
        }

        $stats = [
            'opened_sessions' => $openedSessions,
            'closed_sessions' => 0,
            'price_changes' => 0,
        ];

        return view('tenants.profile.show', [
            'user' => $user,
            'stats' => $stats,
        ]);
    }

    public function edit(): View
    {
        $user = Auth::guard('user')->user();
        abort_if($user === null, 403);

        return view('tenants.profile.edit', ['user' => $user]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = Auth::guard('user')->user();
        abort_if($user === null, 403);

        $data = $request->validated();
        $data['email'] = mb_strtolower((string) $data['email']);

        if (empty($data['password'])) {
            $data = Arr::except($data, ['password']);
        } else {
            $data['password'] = Hash::make((string) $data['password']);
        }

        $user->update($data);

        return to_route('tenant.profile.show')
            ->with('status', 'Profile updated.');
    }
}

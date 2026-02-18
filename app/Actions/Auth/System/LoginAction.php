<?php

declare(strict_types=1);

namespace App\Actions\Auth\System;

use App\Enums\LoginUserType;
use App\Enums\UserStatus;
use App\Models\LoginAttempt;
use App\Models\SystemUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

final class LoginAction
{
    /**
     * Handle system user login with comprehensive security checks.
     */
    public function handle(string $email, string $password, bool $remember = false): void
    {
        $ipAddress = request()->ip();
        $userAgent = request()->userAgent();

        $this->ensureIsNotRateLimited($email, $ipAddress);

        $user = SystemUser::query()->where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            $this->handleFailedLogin($email, $ipAddress, $userAgent, $user?->id, 'invalid_credentials');

            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        if ($user->status !== UserStatus::ACTIVE) {
            $this->handleBlockedLogin($email, $ipAddress, $userAgent, $user->id, $user->status);

            $message = match ($user->status) {
                UserStatus::SUSPENDED => 'Your account has been suspended. Please contact support.',
                UserStatus::INACTIVE => 'Your account is inactive. Please contact support.',
                default => 'Login is not allowed.',
            };

            throw ValidationException::withMessages([
                'email' => [$message],
            ]);
        }

        $ok = Auth::guard('system')->attempt([
            'email' => $email,
            'password' => $password,
            'status' => UserStatus::ACTIVE,
        ], $remember);

        if (! $ok) {
            $this->handleFailedLogin($email, $ipAddress, $userAgent, $user->id, 'auth_failed');

            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $this->handleSuccessfulLogin($user, $ipAddress, $userAgent);

        RateLimiter::clear($this->throttleKey($email, $ipAddress));
    }

    /**
     * Ensure the login request is not rate limited.
     */
    private function ensureIsNotRateLimited(string $email, string $ipAddress): void
    {
        $key = $this->throttleKey($email, $ipAddress);

        if (! RateLimiter::tooManyAttempts($key, 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($key);

        throw ValidationException::withMessages([
            'email' => [trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ])],
        ]);
    }

    /**
     * Get the rate limiting throttle key.
     */
    private function throttleKey(string $email, string $ipAddress): string
    {
        return 'login:system:'.mb_strtolower($email).':'.$ipAddress;
    }

    /**
     * Handle successful login.
     */
    private function handleSuccessfulLogin(SystemUser $user, string $ipAddress, ?string $userAgent): void
    {
        $user->updateLastLogin($ipAddress);

        Log::info('Successful system login', [
            'email' => $user->email,
            'user_id' => $user->id,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        LoginAttempt::query()->create([
            'email' => $user->email,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'status' => 'success',
            'user_id' => $user->id,
            'user_type' => LoginUserType::SYSTEM->value,
            'attempted_at' => now(),
        ]);
    }

    /**
     * Handle failed login attempt.
     */
    private function handleFailedLogin(
        string $email,
        string $ipAddress,
        ?string $userAgent,
        ?string $userId,
        string $reason
    ): void {
        RateLimiter::hit($this->throttleKey($email, $ipAddress), 60);

        Log::warning('Failed system login attempt', [
            'email' => $email,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'reason' => $reason,
        ]);

        LoginAttempt::query()->create([
            'email' => $email,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'status' => 'failed',
            'failure_reason' => $reason,
            'user_id' => $userId,
            'user_type' => LoginUserType::SYSTEM->value,
            'attempted_at' => now(),
        ]);
    }

    /**
     * Handle blocked login attempt (user exists but status prevents login).
     */
    private function handleBlockedLogin(
        string $email,
        string $ipAddress,
        ?string $userAgent,
        string $userId,
        UserStatus $status
    ): void {
        Log::warning('Blocked system login attempt', [
            'email' => $email,
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'status' => $status->value,
        ]);

        LoginAttempt::query()->create([
            'email' => $email,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'status' => 'blocked',
            'failure_reason' => 'user_status_'.$status->value,
            'user_id' => $userId,
            'user_type' => LoginUserType::SYSTEM->value,
            'attempted_at' => now(),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Auth\Tenant;

use App\Enums\LoginUserType;
use App\Models\LoginAttempt;
use App\Models\LoginMap;
use App\Models\Tenant;
use App\Support\AuditTimelineLogger;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

final class LoginAction
{
    public function handle(string $email, string $password, bool $remember = false): array
    {
        $email = mb_strtolower(mb_trim($email));
        $ipAddress = request()->ip();
        $userAgent = request()->userAgent();

        $logins = LoginMap::query()
            ->where('email', $email)
            ->where('type', LoginUserType::USER->value)
            ->where('status', true)
            ->with('tenant:id,name')
            ->get();

        if ($logins->isEmpty()) {
            $this->recordAttempt($email, 'failed', 'invalid_email', null, $ipAddress, $userAgent, []);

            // ✅ Always hash even if email not found (constant-time)
            Hash::check($password, Hash::make('dummy'));

            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        // ✅ Check password against ALL logins (constant time regardless of match position)
        $matchedLogins = collect();

        foreach ($logins as $login) {
            if (Hash::check($password, (string) $login->password)) {
                $matchedLogins->push($login);
            }
        }

        if ($matchedLogins->isEmpty()) {
            $this->recordAttempt(
                $email,
                'failed',
                'invalid_password',
                null,
                $ipAddress,
                $userAgent,
                $logins->pluck('tenant_id')->unique()->values()->all()
            );

            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        // ✅ Single tenant — return directly
        if ($matchedLogins->count() === 1) {
            $login = $matchedLogins->first();
            $this->recordAttempt(
                $email,
                'success',
                null,
                (string) $login->type_id,
                $ipAddress,
                $userAgent,
                [$login->tenant_id]
            );

            return [
                'tenant' => $login->tenant_id,
                'type' => $login->type,
                'type_id' => $login->type_id,
                'remember' => $remember,
            ];
        }

        // ✅ Multiple tenants — return picker flag
        return [
            'multiple_tenants' => true,
            'tenants' => $matchedLogins->map(function (LoginMap $login): array {
                $tenant = $login->tenant;
                $tenantName = $tenant instanceof Tenant
                    ? $tenant->name
                    : 'Tenant '.$login->tenant_id;

                return [
                    'tenant_id' => $login->tenant_id,
                    'tenant_name' => $tenantName,
                    'type_id' => $login->type_id,
                    'type' => $login->type,
                ];
            })->all(),
            'remember' => $remember,
        ];
    }

    private function recordAttempt(
        string $email,
        string $status,
        ?string $failureReason,
        ?string $userId,
        ?string $ipAddress,
        ?string $userAgent,
        array $tenantIds
    ): void {
        rescue(function () use ($email, $status, $failureReason, $userId, $ipAddress, $userAgent, $tenantIds): void {
            $centralConnection = (string) config('tenancy.database.central_connection', config('database.default'));

            if (! Schema::connection($centralConnection)->hasTable('login_attempts')) {
                return;
            }

            LoginAttempt::query()->create([
                'email' => $email,
                'ip_address' => $ipAddress ?? '0.0.0.0',
                'user_agent' => $userAgent,
                'status' => $status,
                'failure_reason' => $failureReason,
                'user_id' => $userId,
                'user_type' => LoginUserType::USER->value,
                'attempted_at' => now(),
            ]);

            if ($status === 'failed') {
                foreach (array_unique($tenantIds) as $tenantId) {
                    if (! is_string($tenantId) || $tenantId === '') {
                        continue;
                    }

                    AuditTimelineLogger::logForTenant(
                        tenantId: $tenantId,
                        event: 'auth_failure',
                        description: 'Tenant login attempt failed.',
                        properties: [
                            'email' => $email,
                            'reason' => $failureReason,
                            'tenant_ids' => $tenantIds,
                            'ip_address' => $ipAddress,
                        ],
                    );
                }
            }
        }, null, false);
    }
}

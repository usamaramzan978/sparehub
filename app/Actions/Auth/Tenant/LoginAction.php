<?php

declare(strict_types=1);

namespace App\Actions\Auth\Tenant;

use App\Enums\LoginUserType;
use App\Models\LoginMap;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class LoginAction
{
    public function handle(string $email, string $password, bool $remember = false): array
    {
        $email = mb_strtolower(mb_trim($email));

        $logins = LoginMap::query()
            ->where('email', $email)
            ->where('type', LoginUserType::USER->value)
            ->where('status', true)
            ->with('tenant:id,name')
            ->get();

        if ($logins->isEmpty()) {
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
            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        // ✅ Single tenant — return directly
        if ($matchedLogins->count() === 1) {
            $login = $matchedLogins->first();

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
            'tenants' => $matchedLogins->map(fn (LoginMap $login) => [
                'tenant_id' => $login->tenant_id,
                'tenant_name' => $login->tenant?->name ?? 'Tenant '.$login->tenant_id,
                'type_id' => $login->type_id,
                'type' => $login->type,
            ])->toArray(),
            'remember' => $remember,
        ];
    }
}

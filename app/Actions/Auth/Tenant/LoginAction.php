<?php

declare(strict_types=1);

namespace App\Actions\Auth\Tenant;

use App\Enums\LoginUserType;
use App\Models\LoginMap;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class LoginAction
{
    public function handle(string $email, string $password, bool $remember = false): array
    {
        $email = mb_strtolower(mb_trim($email));

        $login = LoginMap::query()->where('email', $email)
            ->whereIn('type', [
                LoginUserType::USER->value,
            ])
            ->first();

        if (! $login || ! Hash::check($password, $login->password)) {
            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        // if ($login->status != 1) {
        //     throw ValidationException::withMessages([
        //         'email' => ['Login is blocked'],
        //     ]);
        // }

        $token = Crypt::encrypt(json_encode([
            'email' => $email,
            'password' => $password,
            'remember' => $remember,
            'type' => $login->type,
            'type_id' => $login->type_id,
        ]));

        return [
            'tenant' => $login->tenant_id,
            'token' => $token,
        ];
    }
}

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

        $login = LoginMap::query()
            ->where('email', $email)
            ->where('type', LoginUserType::USER->value)
            ->first();

        if (! $login || ! Hash::check($password, $login->password)) {
            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        return [
            'tenant' => $login->tenant_id,
            'type' => $login->type,
            'type_id' => $login->type_id,
            'remember' => $remember,
        ];
    }
}

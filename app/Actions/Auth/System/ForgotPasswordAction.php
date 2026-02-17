<?php

declare(strict_types=1);

namespace App\Actions\Auth\System;

use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

final class ForgotPasswordAction
{
    public function handle(string $email): string
    {
        $status = Password::broker('system_users')->sendResetLink(['email' => $email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return $status;
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Auth\System;

use Illuminate\Support\Facades\Auth;

final class LogoutAction
{
    public function handle(): void
    {
        Auth::guard('system')->logout();
    }
}

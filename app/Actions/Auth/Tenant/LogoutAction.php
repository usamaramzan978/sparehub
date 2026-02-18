<?php

declare(strict_types=1);

namespace App\Actions\Auth\Tenant;

use Illuminate\Support\Facades\Auth;

final class LogoutAction
{
    public function handle(): void
    {
        // Auth::guard('web')->logout();
        Auth::guard('user')->logout();
    }
}

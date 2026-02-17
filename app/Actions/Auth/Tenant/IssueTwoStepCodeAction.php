<?php

declare(strict_types=1);

namespace App\Actions\Auth\Tenant;

use App\Mail\TenantTwoStepCodeMail;
use App\Models\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Mail;

final class IssueTwoStepCodeAction
{
    public function handle(Session $session, User $user): string
    {
        $code = (string) random_int(1000, 9999);

        $session->put('two_step.code', $code);
        $session->put('two_step.expires_at', now()->addMinutes(10));
        $session->put('two_step.required', true);
        $session->put('two_step.verified', false);

        Mail::to($user->email)->send(new TenantTwoStepCodeMail($code));

        return $code;
    }
}

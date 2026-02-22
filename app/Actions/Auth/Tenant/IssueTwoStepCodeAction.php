<?php

declare(strict_types=1);

namespace App\Actions\Auth\Tenant;

use App\Enums\TwoFactorMethod;
use App\Mail\TenantTwoStepCodeMail;
use App\Models\User;
use App\Support\AuditTimelineLogger;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Mail;

final class IssueTwoStepCodeAction
{
    public function handle(Session $session, User $user): string
    {
        $code = mb_str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $session->put('two_step.code', $code);
        $session->put('two_step.expires_at', now()->addMinutes(10));
        $session->put('two_step.method', TwoFactorMethod::EMAIL->value);
        $session->put('two_step.setup_required', false);
        $session->put('two_step.enrollment_required', false);
        $session->put('two_step.required', true);
        $session->put('two_step.verified', false);

        Mail::to($user->email)->send(new TenantTwoStepCodeMail($code));

        AuditTimelineLogger::log(
            event: 'two_step_code_issued',
            description: 'Two-step verification code issued via email.',
            causer: $user,
            subject: $user,
            properties: [
                'method' => TwoFactorMethod::EMAIL->value,
                'expires_at' => (string) $session->get('two_step.expires_at'),
            ],
        );

        return $code;
    }
}

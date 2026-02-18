<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth\System;

use App\Actions\Auth\System\ForgotPasswordAction;
use App\Actions\Auth\System\LoginAction;
use App\Actions\Auth\System\LogoutAction;
use App\Actions\Auth\System\ResetPasswordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\System\ForgotPasswordRequest;
use App\Http\Requests\Auth\System\LoginRequest;
use App\Http\Requests\Auth\System\ResetPasswordRequest;
use Illuminate\Http\RedirectResponse;

final class AuthController extends Controller
{
    public function login(LoginRequest $request, LoginAction $action): RedirectResponse
    {
        $action->handle(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
            $request->boolean('remember')
        );

        $request->session()->regenerate();

        return to_route('system.dashboard')->with('status', 'Login successful.');
    }

    public function logout(LogoutAction $action): RedirectResponse
    {
        $action->handle();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return to_route('system.login');
    }

    public function forgotPassword(ForgotPasswordRequest $request, ForgotPasswordAction $action): RedirectResponse
    {
        $status = $action->handle($request->string('email')->toString());

        return back()->with('status', __($status));
    }

    public function resetPassword(ResetPasswordRequest $request, ResetPasswordAction $action): RedirectResponse
    {
        $status = $action->handle($request->validated());

        return to_route('system.login')->with('status', __($status));
    }
}

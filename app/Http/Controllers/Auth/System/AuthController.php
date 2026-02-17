<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth\System;

use App\Actions\Auth\System\ForgotPasswordAction;
use App\Actions\Auth\System\LoginAction;
use App\Actions\Auth\System\LogoutAction;
use App\Actions\Auth\System\RegisterAction;
use App\Actions\Auth\System\ResetPasswordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\System\ForgotPasswordRequest;
use App\Http\Requests\Auth\System\LoginRequest;
use App\Http\Requests\Auth\System\RegisterRequest;
use App\Http\Requests\Auth\System\ResetPasswordRequest;
use Illuminate\Http\JsonResponse;

final class AuthController extends Controller
{
    public function login(LoginRequest $request, LoginAction $action): JsonResponse
    {
        $action->handle(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
            $request->boolean('remember')
        );

        $request->session()->regenerate();

        return response()->json([
            'message' => 'Login successful.',
        ]);
    }

    public function logout(LogoutAction $action): JsonResponse
    {
        $action->handle();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return response()->json([
            'message' => 'Logout successful.',
        ]);
    }

    public function register(RegisterRequest $request, RegisterAction $action): JsonResponse
    {
        $action->handle($request->validated());

        return response()->json([
            'message' => 'Registration successful.',
        ], 201);
    }

    public function forgotPassword(ForgotPasswordRequest $request, ForgotPasswordAction $action): JsonResponse
    {
        $status = $action->handle($request->string('email')->toString());

        return response()->json([
            'message' => __($status),
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request, ResetPasswordAction $action): JsonResponse
    {
        $status = $action->handle($request->validated());

        return response()->json([
            'message' => __($status),
        ]);
    }
}

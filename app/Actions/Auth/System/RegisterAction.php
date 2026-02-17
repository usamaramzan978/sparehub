<?php

declare(strict_types=1);

namespace App\Actions\Auth\System;

use App\Enums\UserStatus;
use App\Models\SystemUser;
use Illuminate\Support\Facades\Hash;

final class RegisterAction
{
    /**
     * @param  array{name:string,email:string,password:string}  $data
     */
    public function handle(array $data): SystemUser
    {
        $user = SystemUser::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->status = UserStatus::ACTIVE->value;
        $user->save();

        return $user;
    }
}

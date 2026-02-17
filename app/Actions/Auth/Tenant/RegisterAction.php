<?php

declare(strict_types=1);

namespace App\Actions\Auth\Tenant;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

final class RegisterAction
{
    /**
     * @param  array{name:string,email:string,password:string,phone?:string|null,branch_id?:string|null}  $data
     */
    public function handle(array $data): User
    {
        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
        ]);

        if (Schema::hasColumn($user->getTable(), 'status')) {
            $user->status = UserStatus::ACTIVE->value;
            $user->save();
        }

        return $user;
    }
}

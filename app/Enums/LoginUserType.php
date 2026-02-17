<?php

declare(strict_types=1);

namespace App\Enums;

enum LoginUserType: string
{
    case USER = 'user';
    case SYSTEM = 'system';
}
